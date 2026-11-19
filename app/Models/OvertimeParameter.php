<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeParameter extends Model
{
    use HasFactory;

    protected $fillable = ['hs_12', 'hs_35', 'hs_50', 'hs_100'];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? static::query()->create([
            'hs_12' => 10,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ]);
    }
}
