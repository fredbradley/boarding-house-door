<?php

namespace App\Services;

use App\Models\Screen;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Sabre\VObject\Reader;

class IcsService
{
    public function getCurrentLocation(Screen $screen): ?string
    {
        return $this->getLocationAt($screen, now());
    }

    public function getLocationAt(Screen $screen, CarbonInterface $at): ?string
    {
        if (! $screen->ics_url) {
            return null;
        }

        $ics = $this->fetchCachedIcs($screen->ics_url);

        if (! $ics) {
            return null;
        }

        try {
            $calendar = Reader::read($ics, Reader::OPTION_FORGIVING);
        } catch (\Exception $e) {
            return null;
        }

        if (empty($calendar->VEVENT)) {
            return null;
        }

        foreach ($calendar->VEVENT as $event) {
            try {
                $dtStart = $event->DTSTART->getDateTime();
                $dtEnd = $event->DTEND?->getDateTime() ?? (clone $dtStart)->modify('+1 hour');

                $start = Carbon::instance($dtStart);
                $end = Carbon::instance($dtEnd);

                if ($at->greaterThanOrEqualTo($start) && $at->lessThan($end)) {
                    $location = trim((string) ($event->LOCATION ?? ''));

                    if ($location !== '') {
                        return $location;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function bustCache(Screen $screen): void
    {
        if ($screen->ics_url) {
            cache()->forget('ics_'.md5($screen->ics_url));
        }
    }

    private function fetchCachedIcs(string $url): ?string
    {
        $cacheKey = 'ics_'.md5($url);

        $cached = cache()->get($cacheKey, '__miss__');

        if ($cached !== '__miss__') {
            return $cached ?: null;
        }

        try {
            $body = Http::timeout(10)->get($url)->throw()->body();
        } catch (\Exception $e) {
            $body = '';
        }

        cache()->put($cacheKey, $body, now()->addMinutes(15));

        return $body ?: null;
    }
}
