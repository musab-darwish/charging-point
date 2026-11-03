<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = config('daftar.owner');

        foreach (['name', 'username', 'password'] as $field) {
            if (blank($owner[$field])) {
                throw new RuntimeException('SEED_OWNER_'.strtoupper($field).' is missing in .env');
            }
        }

        User::updateOrCreate(
            ['username' => $owner['username']],
            [
                'name' => $owner['name'],
                'password' => $owner['password'],
                'is_owner' => true,
            ],
        );
    }
}
