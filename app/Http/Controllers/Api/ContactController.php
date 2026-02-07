<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactMail;
use App\Services\SMTP2GOService;

class ContactController extends Controller
{
    public function sendContactEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 400);
        }

        try {
            $adminEmail = 'sales@stiopa-equipment.com';

            if (!$adminEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact form is not properly configured. Please contact the administrator.'
                ], 500);
            }

            $mail = new ContactMail(
                $request->first_name,
                $request->last_name,
                $request->email,
                $request->phone,
                $request->message
            );

            $smtp2goService = new SMTP2GOService();
            $htmlContent = $mail->renderHtmlContent();
            $result = $smtp2goService->sendEmail($adminEmail, $mail->getSubject(), $htmlContent);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contact form submitted successfully. We will get back to you soon.'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send contact form. Please try again later.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Some went wrong. Please try again.',
            ], 500);
        }
    }
}
