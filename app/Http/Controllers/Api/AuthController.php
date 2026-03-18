<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
<<<<<<< HEAD
    /**
     * Validate credentials, issue Sanctum token, return { token, user }.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
=======
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
>>>>>>> 81bfbe3f352f53bb82dd50bd6bce7078f6524cb9
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

<<<<<<< HEAD
        $user->tokens()->where('name', 'api')->delete();
=======
>>>>>>> 81bfbe3f352f53bb82dd50bd6bce7078f6524cb9
        $token = $user->createToken('api')->plainTextToken;

        return $this->respond([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 'Authenticated.', 200);
    }

<<<<<<< HEAD
    /**
     * Revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
=======
    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user?->currentAccessToken()?->delete();
>>>>>>> 81bfbe3f352f53bb82dd50bd6bce7078f6524cb9

        return $this->respond(null, 'Logged out.', 200);
    }

<<<<<<< HEAD
    /**
     * Return authenticated user profile with role.
     */
    public function me(Request $request)
    {
=======
    public function me(Request $request)
    {
        /** @var User $user */
>>>>>>> 81bfbe3f352f53bb82dd50bd6bce7078f6524cb9
        $user = $request->user();

        return $this->respond([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], 'OK.', 200);
    }
}
