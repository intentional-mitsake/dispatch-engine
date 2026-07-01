<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'products'; 
    protected $fillable = [ 
        'name',
        'stock',
        'price'
    ];

    protected $casts = [ 
        'stock' => 'integer',
        'price' => 'decimal:2'
    ];

    protected $attributes = [ 
        'stock' => 0,
        'price' => 0.00
    ];

}
