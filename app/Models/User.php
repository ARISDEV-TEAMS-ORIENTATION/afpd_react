<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'nom',
    'prenom',
    'email',
    'password',
    'telephone',
    'date_inscription',
    'statut',
    'role_id'
];

protected $hidden = [
    'password',
    'remember_token',
];

     // Événements organisés
    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_responsable');
    }

    // Annonces publiées
    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_auteur');
    }

    // Rapports créés
    public function rapports()
    {
        return $this->hasMany(Rapport::class, 'id_createur');
    }

    // Notifications reçues
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Journal
    public function journalActivites()
    {
        return $this->hasMany(JournalActivite::class);
    }
    public function role()
{
    return $this->belongsTo(Role::class);
}

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
