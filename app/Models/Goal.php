<?php
// app/Models/Goal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'target', 'saved', 'deadline'];

    protected $casts = [
        'target' => 'float',
        'saved' => 'float',
        'deadline' => 'date:Y-m-d',
    ];
}