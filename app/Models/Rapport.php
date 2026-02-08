<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_rapport',
        'periode',
        'chemin_fichier',
        'id_createur'
    ];

    public function createur()
    {
        return $this->belongsTo(User::class, 'id_createur');
    }
}
