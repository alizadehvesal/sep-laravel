<?php

declare(strict_types=1);

namespace Vestra\Sep\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SepPayment extends Model
{
    protected $guarded = [];

    public function getTable()
    {
        return (string) config('sep.database.table', parent::getTable());
    }

    protected $casts = [
        'amount' => 'integer',
        'wage' => 'integer',
        'affective_amount' => 'integer',
        'callback_payload' => 'array',
        'verify_payload' => 'array',
        'reverse_payload' => 'array',
        'meta' => 'array',
        'verified_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
