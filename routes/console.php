<?php

use App\Console\Commands\CheckCoverageGaps;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CheckCoverageGaps::class)->everyFiveMinutes();
