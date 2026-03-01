<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_export',
        'format',
        'filtres',
        'file_path',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'filtres' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
