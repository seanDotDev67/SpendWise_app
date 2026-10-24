<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['transaction_id', 'amou8nt', 'type', 'description','date'];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date'
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function category(): BelongsTo{
        return $this->belongsTo(Category::class);
    }
}
