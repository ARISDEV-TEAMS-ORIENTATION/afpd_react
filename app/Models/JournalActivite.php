<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalActivite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'ip_adresse'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
