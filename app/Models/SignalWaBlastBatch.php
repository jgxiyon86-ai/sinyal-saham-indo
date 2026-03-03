<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalWaBlastBatch extends Model
{
    protected $fillable = [
        'admin_id',
        'tier_id',
        'signal_ids',
        'delay_seconds',
        'max_recipients',
        'opening_text',
        'closing_text',
        'image_url',
        'group_messages',
        'status',
        'total_targets',
        'pending_count',
        'sent_count',
        'failed_count',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'signal_ids' => 'array',
            'group_messages' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class, 'tier_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SignalWaBlastTarget::class, 'batch_id');
    }
}

