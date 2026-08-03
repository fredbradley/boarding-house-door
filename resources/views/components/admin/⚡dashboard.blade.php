<?php

use App\Models\ManualEntry;
use App\Models\Screen;
use App\Models\User;
use App\Services\IcsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'screen')]
    public ?int $screenId = null;

    // ── Add entry form ────────────────────────────────────────────────────

    #[Validate('required|string|max:100')]
    public string $heading = '';

    #[Validate('nullable|string|max:100')]
    public string $subheading = '';

    #[Validate('required|date')]
    public string $startsAt = '';

    #[Validate('nullable|date|after:startsAt')]
    public string $endsAt = '';

    // ── ICS settings form ─────────────────────────────────────────────────

    public bool $editingIcs = false;

    #[Validate('nullable|url|max:2048')]
    public string $icsUrl = '';

    // ── Screen settings form ──────────────────────────────────────────────

    public bool $editingSettings = false;

    #[Validate('required|string|max:100')]
    public string $defaultHeading = '';

    #[Validate('nullable|string|max:100')]
    public string $defaultSubheading = '';

    #[Validate('nullable|email|max:255')]
    public string $notificationEmail = '';

    // ── New screen form ────────────────────────────────────────────────────

    public bool $creatingScreen = false;

    #[Validate('required|string|max:100')]
    public string $newScreenName = '';

    // ── Share screen form ──────────────────────────────────────────────────

    public bool $sharingScreen = false;

    #[Validate('required|email')]
    public string $shareEmail = '';

    public function mount(): void
    {
        $this->hydrateSettingsForms();

        $this->startsAt = now()->format('Y-m-d\TH:i');
        $this->endsAt = now()->addHour()->format('Y-m-d\TH:i');
    }

    private function hydrateSettingsForms(): void
    {
        $screen = $this->screen;

        $this->icsUrl = $screen?->ics_url ?? '';
        $this->defaultHeading = $screen?->default_heading ?? 'In School';
        $this->defaultSubheading = $screen?->default_subheading ?? '';
        $this->notificationEmail = $screen?->notification_email ?? '';
        $this->editingIcs = false;
        $this->editingSettings = false;
        $this->sharingScreen = false;
    }

    #[Computed]
    public function screens()
    {
        return Auth::user()?->screens()->orderBy('name')->get() ?? collect();
    }

    #[Computed]
    public function screen(): ?Screen
    {
        $screens = $this->screens;

        if ($this->screenId && $screen = $screens->firstWhere('id', $this->screenId)) {
            return $screen;
        }

        return $screens->first();
    }

    #[Computed]
    public function screenUsers()
    {
        return $this->screen?->users()->orderBy('name')->get() ?? collect();
    }

    #[Computed]
    public function upcomingEntries()
    {
        return $this->screen?->manualEntries()
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('starts_at')
            ->get() ?? collect();
    }

    public function selectScreen(int $screenId): void
    {
        $this->screenId = $screenId;

        unset($this->screen, $this->screenUsers, $this->upcomingEntries);

        $this->hydrateSettingsForms();
    }

    public function createScreen(): void
    {
        $this->validate([
            'newScreenName' => 'required|string|max:100',
        ]);

        $slug = Str::slug($this->newScreenName);
        $slug = Screen::query()->where('slug', $slug)->exists() ? $slug.'-'.Str::random(4) : $slug;

        $screen = Screen::create([
            'slug' => $slug,
            'name' => $this->newScreenName,
        ]);

        $screen->users()->attach(Auth::id());

        $this->newScreenName = '';
        $this->creatingScreen = false;

        unset($this->screens);

        $this->selectScreen($screen->id);
    }

    public function shareScreen(): void
    {
        $this->validate([
            'shareEmail' => 'required|email',
        ]);

        $user = User::where('email', $this->shareEmail)->first();

        if (! $user) {
            $this->addError('shareEmail', 'No user found with that email.');

            return;
        }

        $this->screen?->users()->syncWithoutDetaching($user->id);

        $this->shareEmail = '';
        $this->sharingScreen = false;

        unset($this->screenUsers);
    }

    public function removeUser(int $userId): void
    {
        if ($userId === Auth::id()) {
            return;
        }

        $this->screen?->users()->detach($userId);

        unset($this->screenUsers);
    }

    public function addEntry(): void
    {
        $this->validate([
            'heading' => 'required|string|max:100',
            'subheading' => 'nullable|string|max:100',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after:startsAt',
        ]);

        $this->screen?->manualEntries()->create([
            'heading' => $this->heading,
            'subheading' => $this->subheading ?: null,
            'starts_at' => Carbon::parse($this->startsAt),
            'ends_at' => $this->endsAt ? Carbon::parse($this->endsAt) : null,
        ]);

        $this->heading = '';
        $this->subheading = '';
        $this->startsAt = now()->format('Y-m-d\TH:i');
        $this->endsAt = now()->addHour()->format('Y-m-d\TH:i');

        unset($this->upcomingEntries);
    }

    public function deleteEntry(int $id): void
    {
        $this->screen?->manualEntries()->findOrFail($id)->delete();

        unset($this->upcomingEntries);
    }

    public function saveIcs(): void
    {
        $this->validateOnly('icsUrl');

        $this->screen?->update(['ics_url' => $this->icsUrl ?: null]);

        app(IcsService::class)->bustCache($this->screen);

        $this->editingIcs = false;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'defaultHeading' => 'required|string|max:100',
            'defaultSubheading' => 'nullable|string|max:100',
            'notificationEmail' => 'nullable|email|max:255',
        ]);

        $this->screen?->update([
            'default_heading' => $this->defaultHeading,
            'default_subheading' => $this->defaultSubheading ?: null,
            'notification_email' => $this->notificationEmail ?: null,
        ]);

        $this->editingSettings = false;
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: false);
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white">
    <div class="max-w-2xl mx-auto px-4 py-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold">Door Display Admin</h1>
                @if($this->screen)
                    <a href="{{ route('screen', $this->screen->slug) }}" target="_blank"
                       class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
                        /screen/{{ $this->screen->slug }} ↗
                    </a>
                @endif
            </div>
            <button wire:click="logout" class="text-slate-500 hover:text-slate-300 text-sm transition-colors">
                Sign out
            </button>
        </div>

        {{-- Screen switcher --}}
        <div class="flex items-center gap-2 flex-wrap mb-10">
            @foreach($this->screens as $s)
                <button wire:key="screen-tab-{{ $s->id }}" wire:click="selectScreen({{ $s->id }})"
                        class="text-sm font-medium rounded-lg px-3 py-1.5 transition-colors
                            {{ $this->screen?->id === $s->id ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-slate-200' }}">
                    {{ $s->name }}
                </button>
            @endforeach
            <button wire:click="$toggle('creatingScreen')"
                    class="text-sm font-medium rounded-lg px-3 py-1.5 border border-dashed border-slate-700 text-slate-500 hover:text-slate-300 hover:border-slate-600 transition-colors">
                {{ $creatingScreen ? 'Cancel' : '+ New screen' }}
            </button>
        </div>

        @if($creatingScreen)
            <section class="mb-10">
                <form wire:submit="createScreen" class="bg-slate-900 rounded-xl p-5 space-y-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Screen name</label>
                        <input type="text" wire:model="newScreenName" placeholder="e.g. Matron's House"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('newScreenName') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors">
                        Create screen
                    </button>
                </form>
            </section>
        @endif

        @if(!$this->screen)
            <p class="text-slate-400">No screen found for your account. Create one above to get started.</p>
        @else

        {{-- Add entry form --}}
        <section class="mb-10">
            <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Add Entry</h2>
            <form wire:submit="addEntry" class="bg-slate-900 rounded-xl p-5 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Heading <span class="text-slate-600">(e.g. Out of school.)</span></label>
                        <input type="text" wire:model="heading" placeholder="Out of school."
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('heading') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Subheading <span class="text-slate-600">(optional)</span></label>
                        <input type="text" wire:model="subheading" placeholder="See Matron for emergencies."
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">From</label>
                        <input type="datetime-local" wire:model="startsAt"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('startsAt') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Until <span class="text-slate-600">(leave blank = ongoing)</span></label>
                        <input type="datetime-local" wire:model="endsAt"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('endsAt') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="pt-1">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors">
                        Add entry
                    </button>
                </div>
            </form>
        </section>

        {{-- Upcoming entries --}}
        <section class="mb-10">
            <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Scheduled Entries</h2>

            @forelse($this->upcomingEntries as $entry)
                <div wire:key="entry-{{ $entry->id }}" class="bg-slate-900 rounded-xl p-4 mb-3 flex items-start justify-between gap-4
                    {{ $entry->isActive() ? 'ring-1 ring-indigo-500' : '' }}">
                    <div>
                        @if($entry->isActive())
                            <span class="inline-block text-xs font-medium text-indigo-400 mb-1">● Active now</span>
                        @endif
                        <p class="font-medium text-white">{{ $entry->heading }}</p>
                        @if($entry->subheading)
                            <p class="text-slate-400 text-sm">{{ $entry->subheading }}</p>
                        @endif
                        <p class="text-slate-600 text-xs mt-1">{{ $entry->formattedRange() }}</p>
                    </div>
                    <button wire:click="deleteEntry({{ $entry->id }})"
                            wire:confirm="Delete this entry?"
                            class="text-slate-600 hover:text-red-400 text-sm transition-colors shrink-0 mt-0.5">
                        ✕
                    </button>
                </div>
            @empty
                <p class="text-slate-600 text-sm">No entries scheduled.</p>
            @endforelse
        </section>

        {{-- ICS calendar --}}
        <section class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest">Google Calendar (ICS)</h2>
                <button wire:click="$toggle('editingIcs')" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                    {{ $editingIcs ? 'Cancel' : 'Edit' }}
                </button>
            </div>
            @if($editingIcs)
                <form wire:submit="saveIcs" class="bg-slate-900 rounded-xl p-5 space-y-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">ICS URL</label>
                        <input type="url" wire:model="icsUrl" placeholder="https://calendar.google.com/calendar/ical/…"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" />
                        @error('icsUrl') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-slate-600 text-xs mt-2">Find this in Google Calendar → Settings → your calendar → "Secret address in iCal format".</p>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors">
                        Save
                    </button>
                </form>
            @else
                <div class="bg-slate-900 rounded-xl p-4">
                    @if($this->screen->ics_url)
                        <p class="text-slate-400 text-xs font-mono truncate">{{ $this->screen->ics_url }}</p>
                        <p class="text-slate-600 text-xs mt-1">Calendar connected. Location field is used as the heading.</p>
                    @else
                        <p class="text-slate-500 text-sm">No calendar connected.</p>
                    @endif
                </div>
            @endif
        </section>

        {{-- Screen settings --}}
        <section class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest">Default & Alerts</h2>
                <button wire:click="$toggle('editingSettings')" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                    {{ $editingSettings ? 'Cancel' : 'Edit' }}
                </button>
            </div>
            @if($editingSettings)
                <form wire:submit="saveSettings" class="bg-slate-900 rounded-xl p-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Default heading</label>
                            <input type="text" wire:model="defaultHeading"
                                   class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            @error('defaultHeading') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Default subheading <span class="text-slate-600">(optional)</span></label>
                            <input type="text" wire:model="defaultSubheading"
                                   class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Alert email <span class="text-slate-600">(notified 15 min before a gap)</span></label>
                        <input type="email" wire:model="notificationEmail"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('notificationEmail') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors">
                        Save
                    </button>
                </form>
            @else
                <div class="bg-slate-900 rounded-xl p-4 space-y-1">
                    <p class="text-white text-sm">Default: <span class="text-slate-300">{{ $this->screen->default_heading }}</span>
                        @if($this->screen->default_subheading)
                            <span class="text-slate-500"> / {{ $this->screen->default_subheading }}</span>
                        @endif
                    </p>
                    <p class="text-slate-500 text-xs">
                        Alerts: {{ $this->screen->notification_email ?? 'none configured' }}
                    </p>
                </div>
            @endif
        </section>

        {{-- Shared access --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest">Shared Access</h2>
                <button wire:click="$toggle('sharingScreen')" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                    {{ $sharingScreen ? 'Cancel' : 'Add person' }}
                </button>
            </div>

            @if($sharingScreen)
                <form wire:submit="shareScreen" class="bg-slate-900 rounded-xl p-5 space-y-3 mb-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Email address</label>
                        <input type="email" wire:model="shareEmail" placeholder="colleague@example.com"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        @error('shareEmail') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors">
                        Share screen
                    </button>
                </form>
            @endif

            @foreach($this->screenUsers as $user)
                <div wire:key="user-{{ $user->id }}" class="bg-slate-900 rounded-xl p-4 mb-3 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium text-white text-sm">{{ $user->name }}</p>
                        <p class="text-slate-500 text-xs">{{ $user->email }}</p>
                    </div>
                    @if($user->id !== Auth::id())
                        <button wire:click="removeUser({{ $user->id }})"
                                wire:confirm="Remove this person's access?"
                                class="text-slate-600 hover:text-red-400 text-sm transition-colors shrink-0">
                            ✕
                        </button>
                    @endif
                </div>
            @endforeach
        </section>

        @endif
    </div>
</div>
