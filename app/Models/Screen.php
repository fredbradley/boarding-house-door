<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScreenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    /** @use HasFactory<ScreenFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'ics_url',
        'default_heading',
        'default_subheading',
        'notification_email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manualEntries(): HasMany
    {
        return $this->hasMany(ManualEntry::class);
    }

    public function activeManualEntry(?CarbonInterface $at = null): ?ManualEntry
    {
        $at ??= now();

        return $this->manualEntries()
            ->where('starts_at', '<=', $at)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->orderByDesc('starts_at')
            ->first();
    }
}
