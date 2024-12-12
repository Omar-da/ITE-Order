<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'cart',
        'location',
        'updated'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);  
    }

    public function order()
    {
        return $this->belongsTo(Order::class);  
    }
}
