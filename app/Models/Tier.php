<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tier extends Model
{
    protected $fillable = [
        'name',
        'min_capital',
        'max_capital',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'min_capital' => 'decimal:2',
            'max_capital' => 'decimal:2',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function signals(): BelongsToMany
    {
        return $this->belongsToMany(Signal::class);
    }
}
