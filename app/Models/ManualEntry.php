<?php

namespace App\Models;

use Database\Factories\ManualEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualEntry extends Model
{
    /** @use HasFactory<ManualEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'heading',
        'subheading',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function isActive(): bool
    {
        $now = now();

        return $this->starts_at->lte($now)
            && ($this->ends_at === null || $this->ends_at->gt($now));
    }

    public function formattedRange(): string
    {
        $start = $this->starts_at->format('D j M, H:i');

        if ($this->ends_at === null) {
            return "{$start} onwards";
        }

        if ($this->starts_at->isSameDay($this->ends_at)) {
            return "{$start} – {$this->ends_at->format('H:i')}";
        }

        return "{$start} – {$this->ends_at->format('D j M, H:i')}";
    }
}
