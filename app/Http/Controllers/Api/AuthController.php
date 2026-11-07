<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['sometimes', 'string', 'max:100'],
            'read_only' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->isBanned()) {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        // return response()->json([
        //     'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
        //     'user' => new UserResource($user),
        // ]);
        $abilities = ($data['read_only'] ?? false)
            ? ['posts:read']
            : ['posts:read', 'posts:write'];

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'api', $abilities)->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}