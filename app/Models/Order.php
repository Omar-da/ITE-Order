<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    const DELETED_AT = 'rejected_at';

    protected $fillable = [
        'user_id',
        'cart',
        'location',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
