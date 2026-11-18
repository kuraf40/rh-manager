<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'code', 'employee_id', 'motif',
        'resume', 'nb_jours', 'attribue_par', 'date_enregistrement',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
