<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SumsubController extends Controller
{
    private $token;
    private $secret;
    private $base;
    private $level;

    public function __construct()
    {
        $this->token  = env('SUMSUB_TOKEN');
        $this->secret = env('SUMSUB_SECRET');
        $this->base   = rtrim(env('SUMSUB_BASE_URL','https://api.sumsub.com'), '/');
        $this->level  = env('SUMSUB_LEVEL','id-only');
    }

    private function sign($ts,$method,$url,$body='')
    {
        return hash_hmac('sha256',$ts.$method.$url.$body,$this->secret);
    }

    private function getApplicant($externalUserId)
    {
        $url="/resources/applicants?levelName=".$this->level;
        $ts=time();

        $body=json_encode(["externalUserId"=>$externalUserId]);
        $sig=$this->sign($ts,'POST',$url,$body);

        $ch=curl_init($this->base.$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,[
            "Content-Type: application/json",
            "X-App-Token: ".$this->token,
            "X-App-Access-Sig: ".$sig,
            "X-App-Access-Ts: ".$ts,
        ]);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$body);

        $response=curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Something went wrong, please try again.");
        }
        $data=json_decode($response,true);

        if(isset($data['id'])){
            return $data['id'];
        }

        $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : "Applicant creation failed");
        throw new \Exception($msg);
    }

    private function uploadSide($applicantId,$file,$side,$docType,$country)
    {
        $url="/resources/applicants/$applicantId/info/idDoc";
        $ts=time();

        $metadata=[
            "idDocType"=>strtoupper($docType),
            "country"=>strtoupper($country),
            "idDocSubType"=>$side=="front"?"FRONT_SIDE":"BACK_SIDE"
        ];

        $boundary="----sumsub".md5(time());
        $eol="\r\n";
        $fileContent=file_get_contents($file->getRealPath());

        $fileName = method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : $file->getFilename();

        $body="";
        $body.="--$boundary$eol";
        $body.="Content-Disposition: form-data; name=\"metadata\"$eol$eol";
        $body.=json_encode($metadata).$eol;

        $body.="--$boundary$eol";
        $body.="Content-Disposition: form-data; name=\"content\"; filename=\"".$fileName."\"$eol";
        $body.="Content-Type: ".$file->getMimeType()."$eol$eol";
        $body.=$fileContent.$eol;
        $body.="--$boundary--$eol";

        $sig=$this->sign($ts,'POST',$url,$body);

        $ch=curl_init($this->base.$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,[
            "Content-Type: multipart/form-data; boundary=".$boundary,
            "X-App-Token: ".$this->token,
            "X-App-Access-Sig: ".$sig,
            "X-App-Access-Ts: ".$ts,
        ]);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$body);

        $response=curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Something went wrong, please try again.");
        }
        $http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $data=json_decode($response,true);

        if($http!=200){
            $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : "Document upload failed");
            throw new \Exception($msg);
        }

        return $data;
    }

    private function startVerify($applicantId)
    {
        $url="/resources/applicants/$applicantId/status/pending";
        $ts=time();

        $sig=$this->sign($ts,'POST',$url,"");

        $ch=curl_init($this->base.$url);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");

        curl_setopt($ch,CURLOPT_HTTPHEADER,[
            "X-App-Token: ".$this->token,
            "X-App-Access-Sig: ".$sig,
            "X-App-Access-Ts: ".$ts
        ]);

        $response=curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Something went wrong, please try again.");
        }
        $http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $data=json_decode($response,true);

        if($http!=200){
             $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : "Verification start failed");
             throw new \Exception($msg);
        }

        return $data;
    }

    public function uploadLicense(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'externalUserId'=>'required',
                'docType'=>'required',
                'country'=>'required',
                'front'=>'required|file',
                'back'=>'required|file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation error',
                    'error'   => $validator->errors()->first(),
                ], 422);
            }

            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $externalUserId = $request->externalUserId;
            $docType = $request->docType;
            $country = $request->country;

            $frontFile = $request->file('front');
            $backFile = $request->file('back');

            $existingLicense = License::where('user_id', $user->id)->first();
            $destinationPath = public_path('licenses');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $frontName = time() . '_' . $user->id . '_front.' . $frontFile->getClientOriginalExtension();
            if ($existingLicense && $existingLicense->front_side) {
                $oldPath = public_path($existingLicense->front_side);
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $frontFile->move($destinationPath, $frontName);
            $frontPath = 'licenses/' . $frontName;

            $backName = time() . '_' . $user->id . '_back.' . $backFile->getClientOriginalExtension();
            if ($existingLicense && $existingLicense->back_side) {
                $oldPath = public_path($existingLicense->back_side);
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $backFile->move($destinationPath, $backName);
            $backPath = 'licenses/' . $backName;

            $applicantId = $this->getApplicant($externalUserId);
            
            $frontRes = $this->uploadSide($applicantId, new \Illuminate\Http\File($destinationPath.'/'.$frontName), 'front', $docType, $country);
            $backRes  = $this->uploadSide($applicantId, new \Illuminate\Http\File($destinationPath.'/'.$backName), 'back', $docType, $country);
            
            $verify = $this->startVerify($applicantId);

            $licenseData = [
                'user_id'      => $user->id,
                'applicant_id' => $applicantId,
                'front_side'   => $frontPath,
                'back_side'    => $backPath,
                'status'       => 0,
            ];

            if ($existingLicense) {
                $existingLicense->update($licenseData);
                $license = $existingLicense;
            } else {
                $license = License::create($licenseData);
            }

            $user->update(['is_license' => 0]);

            $this->syncStatus($applicantId);

            return response()->json([
                "status"      => true,
                "message"     => "License uploaded and verification started",
                "applicantId" => $applicantId,
                "license"     => $license,
                "sumsub"      => [
                    "front"  => $frontRes,
                    "back"   => $backRes,
                    "verify" => $verify
                ]
            ]);

        }catch(\Exception $e){
            return response()->json([
                "status"=>false,
                "message"=>"Something went wrong, please try again."
            ],500);
        }
    }

    public function status($applicantId)
    {
        try {
            $data = $this->syncStatus($applicantId);

            return response()->json([
                "status"  => true,
                "message" => "Status retrieved successfully",
                "data"    => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Something went wrong, please try again."
            ], 500);
        }
    }

    private function syncStatus($applicantId)
    {
        $url = "/resources/applicants/" . $applicantId . "/status";
        $ts = time();
        $sig = $this->sign($ts, 'GET', $url);

        $ch = curl_init($this->base . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-App-Token: " . $this->token,
            "X-App-Access-Sig: " . $sig,
            "X-App-Access-Ts: " . $ts,
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Connection Error: " . curl_error($ch));
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($response, true);

        if ($http != 200) {
            $msg = isset($data['description']) ? $data['description'] : (isset($data['message']) ? $data['message'] : "Status retrieval failed");
            throw new \Exception($msg);
        }

        if (isset($data['reviewResult']['reviewAnswer'])) {
            $answer = $data['reviewResult']['reviewAnswer'];
            $license = License::where('applicant_id', $applicantId)->first();

            if ($license) {
                $newStatus = null;
                if ($answer === 'GREEN') {
                    $newStatus = License::STATUS_APPROVED;
                } elseif ($answer === 'RED') {
                    $newStatus = License::STATUS_DECLINED;
                }

                if ($newStatus !== null && $license->status != $newStatus) {
                    $license->update(['status' => $newStatus]);
                    if ($license->user) {
                        $license->user->update(['is_license' => $newStatus]);
                    }
                }
            }
        }

        return $data;
    }
}
