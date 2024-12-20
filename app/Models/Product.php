<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'image',  
        'description',
        'price',
        'market_id',
        'available_quantity'
    ];

    
    public function market(){
        return $this->belongsTo(Market::class);
    }

    public function user_of_cart()
    {
        return $this->belongsToMany(User::class, 'carts')->withPivot(['quantity', 'total_price']);
    }

    public function user_of_favorites()
    {
        return $this->belongsToMany(User::class,'favorites');
    }
}
