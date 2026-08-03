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

        $screen = Screen::query()->firstOrCreate(
            ['slug' => 'frb'],
            [
                'name' => 'FRB',
                'default_heading' => 'In School',
                'default_subheading' => 'Where else?',
                'notification_email' => $user->email,
            ]
        );

        $screen->users()->syncWithoutDetaching([$user->id]);
    }
}
