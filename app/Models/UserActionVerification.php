<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActionVerification extends Model
{
    protected $fillable = [
        'admin_id',
        'user_id',
        'action',
        'email',
        'pending_password',
        'code_hash',
        'expires_at',
        'attempts',
    ];

    protected $hidden = [
        'pending_password',
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'pending_password' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
