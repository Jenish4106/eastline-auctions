<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use App\Services\S3StorageService;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SumsubController extends Controller
{
    private $token;
    private $secret;
    private $base;
    private $level;

    public function __construct()
    {
        $this->token = env('SUMSUB_TOKEN');
        $this->secret = env('SUMSUB_SECRET');
        $this->base = rtrim(env('SUMSUB_BASE_URL', 'https://api.sumsub.com'), '/');
        $this->level = env('SUMSUB_LEVEL', 'id-only');
    }

    private function sign($ts, $method, $url, $body = '')
    {
        return hash_hmac('sha256', $ts . $method . $url . $body, $this->secret);
    }

    private function getApplicant($externalUserId)
    {
        $url = '/resources/applicants?levelName=' . $this->level;
        $ts = time();

        $body = json_encode(['externalUserId' => $externalUserId]);
        $sig = $this->sign($ts, 'POST', $url, $body);

        $ch = curl_init($this->base . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-App-Token: ' . $this->token,
            'X-App-Access-Sig: ' . $sig,
            'X-App-Access-Ts: ' . $ts,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Something went wrong, please try again.');
        }
        $data = json_decode($response, true);

        if (isset($data['id'])) {
            return $data['id'];
        }

        $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : 'Applicant creation failed');
        throw new \Exception($msg);
    }

    private function uploadSide($applicantId, $file, $side, $docType, $country)
    {
        $url = "/resources/applicants/$applicantId/info/idDoc";
        $ts = time();

        $metadata = [
            'idDocType' => strtoupper($docType),
            'country' => strtoupper($country),
            'idDocSubType' => $side == 'front' ? 'FRONT_SIDE' : 'BACK_SIDE'
        ];

        $boundary = '----sumsub' . md5(time());
        $eol = "\r\n";
        $fileContent = file_get_contents($file->getRealPath());

        $fileName = method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : $file->getFilename();

        $body = '';
        $body .= "--$boundary$eol";
        $body .= "Content-Disposition: form-data; name=\"metadata\"$eol$eol";
        $body .= json_encode($metadata) . $eol;

        $body .= "--$boundary$eol";
        $body .= 'Content-Disposition: form-data; name="content"; filename="' . $fileName . "\"$eol";
        $body .= 'Content-Type: ' . $file->getMimeType() . "$eol$eol";
        $body .= $fileContent . $eol;
        $body .= "--$boundary--$eol";

        $sig = $this->sign($ts, 'POST', $url, $body);

        $ch = curl_init($this->base . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'X-App-Token: ' . $this->token,
            'X-App-Access-Sig: ' . $sig,
            'X-App-Access-Ts: ' . $ts,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Something went wrong, please try again.');
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($response, true);

        if ($http != 200) {
            $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : 'Document upload failed');
            throw new \Exception($msg);
        }

        return $data;
    }

    private function startVerify($applicantId)
    {
        $url = "/resources/applicants/$applicantId/status/pending";
        $ts = time();

        $sig = $this->sign($ts, 'POST', $url, '');

        $ch = curl_init($this->base . $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-App-Token: ' . $this->token,
            'X-App-Access-Sig: ' . $sig,
            'X-App-Access-Ts: ' . $ts
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Something went wrong, please try again.');
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($response, true);

        if ($http != 200) {
            $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : 'Verification start failed');
            throw new \Exception($msg);
        }

        return $data;
    }

    public function uploadLicense(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'docType' => 'required',
                'country' => 'required',
                'front' => 'required|file',
                'back' => 'required|file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'error' => $validator->errors()->first(),
                ], 422);
            }

            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $externalUserId = 'USER_' . $user->id . '_' . time();
            $docType = $request->docType;
            $country = $request->country;

            $frontFile = $request->file('front');
            $backFile = $request->file('back');

            $existingLicense = License::where('user_id', $user->id)->first();

            if ($existingLicense && $existingLicense->front_side) {
                S3StorageService::delete($existingLicense->front_side);
            }
            if ($existingLicense && $existingLicense->back_side) {
                S3StorageService::delete($existingLicense->back_side);
            }

            $frontName = time() . '_' . $user->id . '_front.' . $frontFile->getClientOriginalExtension();
            $frontResult = S3StorageService::upload($frontFile, 'licenses', $frontName);
            $frontPath = $frontResult['relative_path'];

            $backName = time() . '_' . $user->id . '_back.' . $backFile->getClientOriginalExtension();
            $backResult = S3StorageService::upload($backFile, 'licenses', $backName);
            $backPath = $backResult['relative_path'];

            $applicantId = null;
            $frontRes = null;
            $backRes = null;
            $verify = null;
            $sumsub_error = null;

            try {
                $applicantId = $this->getApplicant($externalUserId);

                $frontRes = $this->uploadSide($applicantId, $frontFile, 'front', $docType, $country);
                $backRes = $this->uploadSide($applicantId, $backFile, 'back', $docType, $country);

                $verify = $this->startVerify($applicantId);
            } catch (\Exception $e) {
                $sumsub_error = $e->getMessage();
            }

            $licenseData = [
                'user_id' => $user->id,
                'front_side' => $frontPath,
                'back_side' => $backPath,
                'status' => 0,
                'is_sumsub' => 0,
            ];

            if ($applicantId) {
                $licenseData['applicant_id'] = $applicantId;
            }

            if ($existingLicense) {
                $existingLicense->update($licenseData);
                $license = $existingLicense;
            } else {
                $license = License::create($licenseData);
            }

            $user->update(['is_license' => 0]);

            if ($applicantId) {
                try {
                    $this->syncStatus($applicantId);
                } catch (\Exception $e) {
                    Log::error('Failed to sync status for applicant ' . $applicantId . ': ' . $e->getMessage());
                }
            }

            $response = [
                'status' => true,
                'message' => $sumsub_error ? 'License uploaded. Verification pending.' : 'License uploaded and verification started',
                'license' => $license,
            ];

            if (!$sumsub_error) {
                $response['applicantId'] = $applicantId;
                $response['sumsub'] = [
                    'front' => $frontRes,
                    'back' => $backRes,
                    'verify' => $verify
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }

    public function status($applicantId)
    {
        try {
            $data = $this->syncStatus($applicantId);

            return response()->json([
                'status' => true,
                'message' => 'Status retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $license = License::where('user_id', $user->id)->first();

            if (!$license || !$license->applicant_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'No applicant ID found for the user.'
                ], 404);
            }

            $data = $this->syncStatus($license->applicant_id);

            return response()->json([
                'status' => true,
                'message' => 'Status retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function syncStatus($applicantId)
    {
        $url = '/resources/applicants/' . $applicantId . '/one';
        $ts = time();
        $sig = $this->sign($ts, 'GET', $url);

        $ch = curl_init($this->base . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-App-Token: ' . $this->token,
            'X-App-Access-Sig: ' . $sig,
            'X-App-Access-Ts: ' . $ts,
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Connection Error: ' . curl_error($ch));
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($response, true);

        if ($http != 200) {
            $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : 'Status retrieval failed');
            throw new \Exception($msg);
        }

        if (isset($data['review']['reviewResult']['reviewAnswer'])) {
            $answer = $data['review']['reviewResult']['reviewAnswer'];
            $license = License::where('applicant_id', $applicantId)->first();

            if ($license) {
                $newStatus = null;
                $setIsSumsub = ($answer === 'GREEN' || $answer === 'RED') ? 1 : 0;

                if ($answer === 'GREEN') {
                    $newStatus = License::STATUS_APPROVED;
                } elseif ($answer === 'RED') {
                    $newStatus = License::STATUS_DECLINED;
                }

                $updates = [];
                if ($newStatus !== null && $license->status != $newStatus) {
                    $updates['status'] = $newStatus;
                }
                if ($setIsSumsub && !$license->is_sumsub) {
                    $updates['is_sumsub'] = 1;
                }

                if (!empty($updates)) {
                    $license->update($updates);
                    if (isset($updates['status']) && $license->user) {
                        $license->user->update(['is_license' => $updates['status']]);

                        if ($updates['status'] == License::STATUS_APPROVED) {
                            (new TwilioSmsService())->sendMessage(
                                $license->user->phone_no,
                                'Welcome to Mcfarland Equipment Sales & Auctions! Your registration is complete. Start browsing, bidding, or use Buy It Now.'
                            );
                        }
                    }
                }
            }
        }

        return $data;
    }
}
