<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscriptionEvenement extends Model
{
    use HasFactory;

    protected $table = 'inscription_evenements';

    protected $fillable = [
        'user_id',
        'evenement_id',
        'date_inscription',
        'presence',
        'date_presence',
        'statut_inscription',
    ];

    protected function casts(): array
    {
        return [
            'presence' => 'boolean',
            'date_inscription' => 'datetime',
            'date_presence' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }
}
