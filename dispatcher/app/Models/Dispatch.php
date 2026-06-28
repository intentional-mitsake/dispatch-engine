<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    // the dispacth model is a representation of the dispatches table
    // it is pretty much the ORM instance of the table that laravel uses to ref the db

    // laravel auto recognizes the table name from the class name, and stuff liek fillable and casts
    protected $table = 'dispatches'; // this is the table name
    protected $fillable = [ // defines fields that can be mass assigned or be assigned by the fill method(like create method)
        'type',
        'payload',
        'status',
        'attempts',
        'idempotency_key',
        'claimed_by',
        'claimed_at',
        'available_at',
        'failed_at'
    ];

    protected $casts = [ // converts Postgres data types to PHP data types
        'payload' => 'array',
        'attempts' => 'integer',
        'claimed_at' => 'datetime',
        'available_at' => 'datetime',
        'failed_at' => 'datetime'
        // the other 4 fields wre string, auto string in PHP also, only needed to convert these
    ];

    protected $attributes = [ // default values
        'status' => 'pending',
        'attempts' => 0,
    ];
}
