<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'lieu',
        'id_responsable',
        'statut'
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'id_responsable');
    }

    public function participants()
    {
        return $this->belongsToMany(
            User::class,
            'inscription_evenements'
        )->withPivot('presence')->withTimestamps();
    }
}

