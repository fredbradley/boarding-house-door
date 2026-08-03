<?php

use App\Models\Screen;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('clicking the hidden refresh action busts the ics cache and re-fetches the calendar', function () {
    $screen = Screen::factory()->create(['ics_url' => 'https://example.com/calendar.ics']);

    $cacheKey = 'ics_'.md5($screen->ics_url);
    cache()->put($cacheKey, 'stale-body-from-before-the-edit', now()->addMinutes(15));

    Http::fake([
        'https://example.com/calendar.ics' => Http::response('fresh-body-after-the-edit'),
    ]);

    Livewire::test('screen-display', ['slug' => $screen->slug])
        ->call('refreshCalendar')
        ->assertOk();

    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/calendar.ics');
    expect(cache()->get($cacheKey))->toBe('fresh-body-after-the-edit');
});

test('the refresh action is a no-op when the screen has no ics url', function () {
    $screen = Screen::factory()->create(['ics_url' => null]);

    Livewire::test('screen-display', ['slug' => $screen->slug])
        ->call('refreshCalendar')
        ->assertOk();
});
