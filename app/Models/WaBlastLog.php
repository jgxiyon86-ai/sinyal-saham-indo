<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBlastLog extends Model
{
    protected $fillable = [
        'admin_id',
        'message_template_id',
        'blast_type',
        'filters',
        'recipients_count',
        'rendered_messages',
        'status',
        'blasted_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'blasted_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }
}
