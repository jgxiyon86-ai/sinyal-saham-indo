<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Signal extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'stock_code',
        'signal_type',
        'entry_price',
        'take_profit',
        'stop_loss',
        'note',
        'published_at',
        'expires_at',
        'push_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_price' => 'decimal:2',
            'take_profit' => 'decimal:2',
            'stop_loss' => 'decimal:2',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'push_sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(Tier::class);
    }
}
