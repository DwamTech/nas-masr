<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $existingUser = User::where('phone', $data['phone'])->first();

        if ($existingUser) {

            if ($existingUser->status !== 'active') {
                return response()->json(['message' => 'User is inactive. Please contact support.'], 403);
            }

            if (!Hash::check($data['password'], $existingUser->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            if (!empty($data['referral_code']) && $existingUser->referral_code !== $data['referral_code']) {
                return response()->json(['message' => 'Invalid referral code'], 401);
            }

            $message = "User logged in successfully.";
            $user = $existingUser;
        } else {

            if (!empty($data['referral_code'])) {

                $code = UserClient::where('user_id', $data['referral_code'])->first();
                if (!$code) {
                    return response([
                        'message' => 'Referral code not found'
                    ], 404);
                }
            }

            $user = User::create([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'],
                'role' => 'user',
                'password' => Hash::make($data['password']),
                'country_code' => $data['country_code'] ?? null,
                'referral_code' => $data['referral_code'] ?? null,
            ]);
            if (!empty($data['referral_code'])) {
                $clients = $code->clients ?? [];


                if (in_array($user->id, $clients)) {
                    return response()->json([
                        "message" => "You have already used this referral code."
                    ]);
                }

                $clients[] = $user->id;


                $code->clients = $clients;
                $code->save();
            }


            $message = "User registered successfully.";
        }

        $token = $user->createToken('nasmasr_token')->plainTextToken;

        return response()->json([
            'message' => $message,
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }


    // admin change  password
    public function changePass(User $user)
    {

        $user->password = Hash::make('123456789');
        $user->save();
        return response()->json(['message' => 'Password changed successfully']);
    }

}
