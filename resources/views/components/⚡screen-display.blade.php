<?php

use App\Models\Screen;
use App\Services\IcsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $slug;

    #[Computed]
    public function screen(): ?Screen
    {
        return Screen::where('slug', $this->slug)->first();
    }

    public function refreshCalendar(): void
    {
        if ($this->screen) {
            app(IcsService::class)->bustCache($this->screen);
        }
    }

    /**
     * @return array{heading: string, subheading: string|null, source: string}
     */
    #[Computed]
    public function content(): array
    {
        $screen = $this->screen;

        if (! $screen) {
            return ['heading' => 'Screen not found.', 'subheading' => null, 'source' => 'error'];
        }

        // 1. Active manual entry takes priority
        $entry = $screen->activeManualEntry();

        if ($entry) {
            return [
                'heading' => $entry->heading,
                'subheading' => $entry->subheading,
                'source' => 'manual',
            ];
        }

        // 2. Active calendar event with a location
        $location = app(IcsService::class)->getCurrentLocation($screen);

        if ($location) {
            return [
                'heading' => $location,
                'subheading' => null,
                'source' => 'calendar',
            ];
        }

        // 3. Default state
        return [
            'heading' => $screen->default_heading,
            'subheading' => $screen->default_subheading,
            'source' => 'default',
        ];
    }
};
?>

<div wire:poll.60s.keep-alive class="min-h-screen bg-slate-950 flex flex-col items-center justify-center px-10 py-12 relative select-none">

    @if($this->screen)
        {{-- Main content --}}
        <div class="text-center w-full max-w-3xl">
            <p wire:click="refreshCalendar" class="text-slate-400 font-medium tracking-widest uppercase mb-6" style="font-size: clamp(1rem, 2.5vw, 1.5rem)">
                Where is {{ $this->screen->name }}?
            </p>
            <h1 class="text-white font-bold tracking-tight leading-none mb-8" style="font-size: clamp(3.5rem, 10vw, 7rem)">
                {{ $this->content['heading'] }}
            </h1>

            @if($this->content['subheading'])
                <p class="text-slate-300 font-light leading-snug" style="font-size: clamp(1.5rem, 4vw, 2.5rem)">
                    {{ $this->content['subheading'] }}
                </p>
            @endif
        </div>

        {{-- Clock --}}
        <p class="absolute bottom-8 right-8 text-slate-600 tabular-nums text-sm font-mono">
            {{ now()->format('H:i') }}
        </p>
    @else
        <p class="text-red-400 text-2xl font-medium">Screen not found.</p>
    @endif

</div>
