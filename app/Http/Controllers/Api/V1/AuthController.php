<?php

namespace App\Http\Controllers\Api\V1;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->profile()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'account created successfully!', 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ApiResponse::error('the provided credentials are incorrect.', 401);
        }

        $token = $user->createToken('auth_token', ['*'], now()->addDays(2))->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'login successful and token generated.');

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'logout successful and token revoked.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success($request->user()->load('profile'), 'identity data fetched successfully.');
    }
}
