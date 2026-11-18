<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'matricule',
        'site',
        'nom',
        'prenom',
        'naissance',
        'genre',
        'fonction',
        'embauche',
        'poste_specifique',
        'hierarchie',
        'categorie',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredHours(): HasMany
    {
        return $this->hasMany(RegisteredHour::class);
    }

    public function disciplinaryActions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class);
    }
}
