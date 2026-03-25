<?php

namespace Database\Seeders;

use App\Models\Screen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ScreenSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'frb@example.com'],
            [
                'name' => 'FRB',
                'password' => Hash::make('password'),
            ]
        );

        Screen::query()->firstOrCreate(
            ['slug' => 'frb'],
            [
                'user_id' => $user->id,
                'name' => 'FRB',
                'default_heading' => 'In School',
                'default_subheading' => 'Where else?',
                'notification_email' => $user->email,
            ]
        );
    }
}
