<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'user_id',
        'lu'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

