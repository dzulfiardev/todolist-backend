<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodoLists extends Model
{
    protected $table = 'todo_lists';

    protected $fillable = [
        'title',
        'assigne',
        'due_date',
        'time_tracked',
        'status',
        'priority',
        'type',
        'estimated_sp',
        'actual_sp'
    ];

    protected $casts = [
        'due_date' => 'date',
        'time_tracked' => 'integer',
        'estimated_sp' => 'integer',
        'actual_sp' => 'integer'
    ];
}
