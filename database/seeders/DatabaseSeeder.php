<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );
        $user->assignRole('user');

        $colleague = User::query()->firstOrCreate(
            ['email' => 'colleague@example.com'],
            ['name' => 'Colleague', 'password' => 'password'],
        );
        $colleague->assignRole('user');
    }
}
