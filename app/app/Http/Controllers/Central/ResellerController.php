<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ResellerController extends Controller
{
    public function index(): View
    {
        return view('central.resellers.index', [
            'resellers' => User::query()
                ->role('reseller')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Role::findOrCreate('reseller', 'web');

        $reseller = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        $reseller->assignRole('reseller');

        return back()->with('status', 'reseller-created');
    }
}
