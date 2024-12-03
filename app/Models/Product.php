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
}
