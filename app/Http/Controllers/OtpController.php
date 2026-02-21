<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string', // E.164 format e.g. +201226099886
        ]);

        $phone = $request->input('phone');
        
        try {
            $twilio = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );

            $verification = $twilio->verify->v2->services(config('services.twilio.verify_service_sid'))
                ->verifications
                ->create($phone, 'whatsapp');

            return response()->json([
                'status' => $verification->status,
                'sid' => $verification->sid,
                'to' => $verification->to,
                'channel' => $verification->channel,
                'ok' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Twilio Send OTP Error: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');

        try {
            $twilio = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );

            $verificationCheck = $twilio->verify->v2->services(config('services.twilio.verify_service_sid'))
                ->verificationChecks
                ->create([
                    'to' => $phone,
                    'code' => $code
                ]);

            return response()->json([
                'ok' => $verificationCheck->status === 'approved',
                'status' => $verificationCheck->status
            ]);

        } catch (\Exception $e) {
            Log::error('Twilio Verify OTP Error: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
