<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users (filter by role).
     */
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'is_active' => $u->is_active,
        ]);

        return $this->respond($users, 'OK.');
    }

    /**
     * Create judge, MC, or organizer account.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_MC, User::ROLE_ORGANIZER])],
            'is_active' => ['boolean'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = $request->user()->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        return $this->respond([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ], 'User created.', 201);
    }

    /**
     * Show single user.
     */
    public function show(User $user)
    {
        return $this->respond([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ], 'OK.');
    }

    /**
     * Update name, email, is_active. Cannot change own role or deactivate self.
     */
    public function update(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return $this->error('You cannot update your own account via this endpoint.', 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return $this->respond([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ], 'User updated.');
    }

    /**
     * Soft delete user. Cannot delete self.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return $this->error('You cannot delete your own account.', 403);
        }

        $user->delete();

        return $this->respond(null, 'User deleted.', 204);
    }
}
