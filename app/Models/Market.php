<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'image'
    ];

    public function products(){
        // return $this->hasMany(Product::class);
    }
}
