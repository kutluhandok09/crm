<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('reseller', 'web');
        Role::findOrCreate('company-user', 'web');

        $admin = User::query()
            ->where('username', 'superadmin')
            ->orWhere('email', 'admin@domain.com')
            ->first();

        if (! $admin) {
            $admin = User::query()->create([
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'admin@domain.com',
                'password' => Hash::make('Admin12345!'),
            ]);
        }

        $admin->assignRole('super-admin');
    }
}
