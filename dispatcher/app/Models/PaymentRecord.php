<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    protected $table = 'payment_rec'; 
    protected $fillable = [
        'dispatch_id',
        'status',
        'customer_id'
    ];

    protected $casts = [
        'dispatch_id' => 'integer',
        'status' => 'string',
        'customer_id' => 'string'
    ];

    protected $attributes = [
        'status' => 'pending'
    ];

}
