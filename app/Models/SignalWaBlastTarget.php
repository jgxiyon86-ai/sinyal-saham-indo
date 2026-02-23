<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalWaBlastTarget extends Model
{
    protected $fillable = [
        'batch_id',
        'client_id',
        'tier_id',
        'signal_id',
        'client_name',
        'signal_title',
        'whatsapp_number',
        'message',
        'image_url',
        'status',
        'attempts',
        'last_error',
        'response_payload',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SignalWaBlastBatch::class, 'batch_id');
    }

    public function signal(): BelongsTo
    {
        return $this->belongsTo(Signal::class, 'signal_id');
    }
}

