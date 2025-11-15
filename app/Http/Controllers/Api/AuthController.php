<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::where('phone', $data['phone'])->first();
        if (!$user) {
            $user = User::create([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'],
                'role' => 'user',
                'password' => Hash::make($data['password']),
                'agent_number' => $data['agent_number'] ?? null,
            ]);

            $message = 'User registered successfully.';
        } else {
            if ($user->status !== 'active') {
                return response()->json(['message' => 'User is inactive. Please contact support.'], 403);
            }
            if (!Hash::check($data['password'], $user->password)) {

                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            $message = 'User logged in successfully.';
        }

        $token = $user->createToken('nasmasr_token')->plainTextToken;

        return response()->json([
            'message' => $message,
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
