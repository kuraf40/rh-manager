<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisteredHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'date_enreg', 'heure_debut', 'heure_fin',
        'total_heures', 'h_jour', 'h_nuit', 'h_ferie_jour',
        'h_ferie_nuit', 'mois_reference', 'semaine_reference',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
