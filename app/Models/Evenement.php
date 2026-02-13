<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    public const STATUT_PENDING = 'pending';
    public const STATUT_ACTIF = 'actif';
    public const STATUT_REFUSE = 'refuse';

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'lieu',
        'image',
        'id_responsable',
        'statut'
    ];

    protected $appends = [
        'image_url'
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

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $relativeUrl = '/storage/' . ltrim($this->image, '/');
        $request = request();

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . $relativeUrl;
        }

        return $relativeUrl;
    }
}
