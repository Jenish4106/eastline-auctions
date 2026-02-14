<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SumsubController extends Controller
{
    private $baseUrl = "https://api.sumsub.com";
    private $token;
    private $secret;
    private $level;

    public function __construct()
    {
        $this->token  = "sbx:fKwzVkM3Ttjb24zZcd56jNMz.jofLYTY1CD6gu46gDcxJKCOn6KLE1lpm";
        $this->secret = "VCTwEXO76HCpevicIWZv4etsdR3oHEb5";
        $this->level  = "id-only";
    }

    public function uploadLicense(Request $req)
    {
        try {

            $externalUserId = $req->externalUserId ?? time();
            $country = $req->country ?? "IND";

            $applicantId = $this->getOrCreateApplicant($externalUserId);

            if($req->hasFile('front')){
                $this->uploadDoc($applicantId,$req->file('front'),$country,'FRONT_SIDE');
            }

            if($req->hasFile('back')){
                $this->uploadDoc($applicantId,$req->file('back'),$country,'BACK_SIDE');
            }

            $this->startVerification($applicantId);

            return response()->json([
                "status"=>true,
                "applicantId"=>$applicantId
            ]);

        } catch(\Exception $e){
            return response()->json([
                "status"=>false,
                "error"=>$e->getMessage()
            ]);
        }
    }

    /* =====================================================
       SIGNATURE HELPER
    ===================================================== */
    private function sign($method,$path,$body="")
    {
        $ts=time();
        $data=$ts.$method.$path.$body;
        $sig=hash_hmac('sha256',$data,$this->secret);

        return [$ts,$sig];
    }

    /* =====================================================
       CREATE / GET APPLICANT
    ===================================================== */
    private function getOrCreateApplicant($externalUserId)
    {
        $encoded=urlencode($externalUserId);
        $path="/resources/applicants/-;externalUserId=".$encoded."/one";

        list($ts,$sig)=$this->sign("GET",$path);

        $ch=curl_init($this->baseUrl.$path);

        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>[
                "X-App-Token: ".$this->token,
                "X-App-Access-Ts: ".$ts,
                "X-App-Access-Sig: ".$sig
            ]
        ]);

        $res=curl_exec($ch);
        $http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($http==200){
            $data=json_decode($res,true);
            return $data['id'];
        }

        // CREATE
        $path="/resources/applicants?levelName=".$this->level;

        $body=json_encode([
            "externalUserId"=>$externalUserId
        ],JSON_UNESCAPED_SLASHES);

        list($ts,$sig)=$this->sign("POST",$path,$body);

        $ch=curl_init($this->baseUrl.$path);

        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POSTFIELDS=>$body,
            CURLOPT_HTTPHEADER=>[
                "Content-Type: application/json",
                "X-App-Token: ".$this->token,
                "X-App-Access-Ts: ".$ts,
                "X-App-Access-Sig: ".$sig
            ]
        ]);

        $res=curl_exec($ch);
        $http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($http>=200 && $http<300){
            $data=json_decode($res,true);
            return $data['id'];
        }

        throw new \Exception($res);
    }

    /* =====================================================
       UPLOAD DOC (MAIN FIX HERE)
    ===================================================== */
    private function uploadDoc($applicantId,$file,$country,$side)
    {
        $path="/resources/applicants/$applicantId/info/idDoc";

        // ⚠️ multipart upload → body include નહિ કરવો
        list($ts,$sig)=$this->sign("POST",$path);

        $post=[
            "metadata"=>json_encode([
                "idDocType"=>"DRIVERS",
                "country"=>$country,
                "idDocSubType"=>$side
            ]),
            "content"=> new \CURLFile(
                $file->getRealPath(),
                $file->getMimeType(),
                $file->getClientOriginalName()
            )
        ];

        $ch=curl_init($this->baseUrl.$path);

        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POSTFIELDS=>$post,
            CURLOPT_HTTPHEADER=>[
                "X-App-Token: ".$this->token,
                "X-App-Access-Ts: ".$ts,
                "X-App-Access-Sig: ".$sig
            ]
        ]);

        $res=curl_exec($ch);
        $http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($http>=200 && $http<300){
            return true;
        }

        throw new \Exception($res);
    }

    /* =====================================================
       START VERIFICATION
    ===================================================== */
    private function startVerification($applicantId)
    {
        $path="/resources/applicants/$applicantId/status/pending";

        list($ts,$sig)=$this->sign("POST",$path);

        $ch=curl_init($this->baseUrl.$path);

        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>[
                "X-App-Token: ".$this->token,
                "X-App-Access-Ts: ".$ts,
                "X-App-Access-Sig: ".$sig
            ]
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
