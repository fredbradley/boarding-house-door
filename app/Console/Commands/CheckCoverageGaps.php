<?php

namespace App\Console\Commands;

use App\Models\Screen;
use App\Notifications\CoverageGapAlert;
use App\Services\IcsService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('screens:check-gaps')]
#[Description('Alert masters when their door display will have no coverage within 15 minutes')]
class CheckCoverageGaps extends Command
{
    public function handle(IcsService $icsService): void
    {
        $screens = Screen::query()->whereNotNull('notification_email')->get();

        foreach ($screens as $screen) {
            $this->checkScreen($screen, $icsService);
        }
    }

    private function checkScreen(Screen $screen, IcsService $icsService): void
    {
        $checkAt = now()->addMinutes(15);

        $hasManualEntry = $screen->manualEntries()
            ->where('starts_at', '<=', $checkAt)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $checkAt))
            ->exists();

        if ($hasManualEntry) {
            return;
        }

        $hasCalendarCoverage = $icsService->getLocationAt($screen, $checkAt) !== null;

        if ($hasCalendarCoverage) {
            return;
        }

        // Deduplicate: only notify once per hour-long window
        $windowKey = 'gap_notified_'.$screen->id.'_'.now()->format('Y-m-d-H');

        if (cache()->has($windowKey)) {
            return;
        }

        Notification::route('mail', $screen->notification_email)
            ->notify(new CoverageGapAlert($screen, Carbon::instance($checkAt)));

        cache()->put($windowKey, true, now()->addHour());

        $this->line("Notified {$screen->notification_email} about gap on screen '{$screen->slug}'");
    }
}
