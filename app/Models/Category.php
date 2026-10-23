<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['userr_id', 'name','type', 'color', 'icon', 'is_archived'];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
}
