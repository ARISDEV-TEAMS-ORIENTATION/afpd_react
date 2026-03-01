<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'avatar_path',
        'theme',
        'language',
        'timezone',
        'email_notifications',
        'push_notifications',
        'show_phone',
        'show_email',
        'profile_visibility',
        'date_inscription',
        'statut',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_responsable');
    }

    public function participations()
    {
        return $this->belongsToMany(Evenement::class, 'inscription_evenements')
            ->withPivot(['presence', 'date_inscription', 'date_presence', 'statut_inscription'])
            ->withTimestamps();
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_auteur');
    }

    public function rapports()
    {
        return $this->hasMany(Rapport::class, 'id_createur');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function journalActivites()
    {
        return $this->hasMany(JournalActivite::class);
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function exportsGenerated()
    {
        return $this->hasMany(Export::class, 'generated_by');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_inscription' => 'datetime',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'show_phone' => 'boolean',
            'show_email' => 'boolean',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://')) {
            return $this->avatar_path;
        }

        $relativeUrl = '/storage/' . ltrim($this->avatar_path, '/');
        $request = request();

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . $relativeUrl;
        }

        return $relativeUrl;
    }
}
