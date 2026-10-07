<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['email'] = strtolower($data['email']);
        $user = User::create([...$data, 'role' => 'customer', 'active' => true]);

        return response()->json(['data' => ['user' => new UserResource($user), 'token' => $user->createToken('api')->plainTextToken]], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->validated('email')))->first();
        if (! $user || ! $user->active || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Email or password is incorrect']], 401);
        }

        return response()->json(['data' => ['user' => new UserResource($user), 'token' => $user->createToken('api')->plainTextToken]]);
    }

    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }
}
