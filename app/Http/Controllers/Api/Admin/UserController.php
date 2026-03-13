<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'mc', 'organizer'])],
            'is_active' => ['boolean'],
        ]);

        /** @var User $creator */
        $creator = $request->user();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $creator->id,
        ]);

        return response()->json(['data' => $user], 201);
    }

    public function show(User $user)
    {
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', Rule::in(['super_admin', 'admin', 'mc', 'organizer'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['password']) && $data['password'] === '') {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['data' => $user]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([], 204);
    }
}

