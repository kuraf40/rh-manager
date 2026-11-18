<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeParameter extends Model
{
    use HasFactory;

    protected $fillable = ['hs_12', 'hs_35', 'hs_50', 'hs_100'];
}