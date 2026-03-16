<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;


class OtpController extends Controller
{


    public function send(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^\+\d{10,15}$/'],
        ]);

        $phone = $request->input('phone');

        $serviceSid = config('services.twilio.verify_service_sid');
        $accountSid = config('services.twilio.account_sid');

        try {
            $twilio = new Client(
                $accountSid,
                config('services.twilio.auth_token')
            );

            $verification = $twilio->verify->v2
                ->services($serviceSid)
                ->verifications
                ->create($phone, 'whatsapp');

            // ✅ تحويل DateTime إلى string بشكل آمن
            $dateCreated = null;
            if (!empty($verification->dateCreated)) {
                // Twilio غالبًا بيرجع DateTime
                $dateCreated = $verification->dateCreated instanceof \DateTimeInterface
                    ? $verification->dateCreated->format('c')   // ISO8601
                    : (string) $verification->dateCreated;
            }

            Log::info('twilio_verify_send', [
                'service_sid_used' => $serviceSid,
                'account_sid_used' => $accountSid,
                'to' => $verification->to ?? null,
                'channel' => $verification->channel ?? null,
                'status' => $verification->status ?? null,
                'sid' => $verification->sid ?? null,
                'date_created' => $dateCreated,
            ]);

            // لو لأي سبب القناة مش WhatsApp اعتبريها فشل
            if (($verification->channel ?? null) !== 'whatsapp') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Twilio did NOT use WhatsApp (fallback happened or channel not available).',
                    'service_sid_used' => $serviceSid,
                    'status' => $verification->status ?? null,
                    'channel' => $verification->channel ?? null,
                    'sid' => $verification->sid ?? null,
                    'to' => $verification->to ?? null,
                ], 400);
            }

            return response()->json([
                'ok' => true,
                'message' => 'WhatsApp verification requested successfully.',
                'service_sid_used' => $serviceSid,
                'status' => $verification->status ?? null,
                'channel' => $verification->channel ?? null,
                'sid' => $verification->sid ?? null,
                'to' => $verification->to ?? null,
            ]);

        } catch (TwilioException $e) {
            Log::error('twilio_verify_error', [
                'service_sid_used' => $serviceSid,
                'to' => $phone,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'more_info' => method_exists($e, 'getMoreInfo') ? $e->getMoreInfo() : null,
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'more_info' => method_exists($e, 'getMoreInfo') ? $e->getMoreInfo() : null,
                'service_sid_used' => $serviceSid,
            ], 500);
        }
    }


  
public function debugVerification(Request $request)
{
    $request->validate([
        'sid' => ['required', 'string'],
    ]);

    $sid = $request->input('sid');
    $serviceSid = config('services.twilio.verify_service_sid');

    $twilio = new Client(
        config('services.twilio.account_sid'),
        config('services.twilio.auth_token')
    );

    $v = $twilio->verify->v2
        ->services($serviceSid)
        ->verifications($sid)
        ->fetch();

    return response()->json([
        'sid' => $v->sid,
        'to' => $v->to,
        'channel' => $v->channel,
        'status' => $v->status,
        'date_created' => $v->dateCreated instanceof \DateTimeInterface ? $v->dateCreated->format('c') : (string)$v->dateCreated,
    ]);
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
