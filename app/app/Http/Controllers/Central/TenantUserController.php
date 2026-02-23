<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('manageUsers', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            'role' => ['required', 'in:owner,manager,staff'],
        ]);

        Role::findOrCreate('company-user', 'web');

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('company-user');
        $tenant->users()->syncWithoutDetaching([
            $user->getKey() => ['role' => $validated['role']],
        ]);

        return back()->with('status', 'tenant-user-created');
    }
}
