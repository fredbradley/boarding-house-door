<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], remember: true)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: false);
    }
};
?>

<div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <h1 class="text-white text-2xl font-bold mb-8 text-center tracking-tight">Door Display</h1>

        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-400 mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-400 mb-1">Password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2.5 text-sm transition-colors"
            >
                Sign in
            </button>
        </form>
    </div>
</div>
