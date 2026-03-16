<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
    * Determine where to redirect the user after login based on role.
    */
    public function toResponse($request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $redirect = match ($user->role ?? null) {
            'super_admin' => '/admin/dashboard',
            'admin' => '/judge/dashboard',
            'mc' => '/mc/reveal',
            'organizer' => '/organizer/dashboard',
            default => config('fortify.home'),
        };

        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false, 'redirect' => $redirect], 200);
        }

        return redirect()->intended($redirect);
    }
}

