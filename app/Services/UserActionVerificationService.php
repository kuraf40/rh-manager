<?php

namespace App\Services;

use App\Mail\UserActionVerificationCode;
use App\Models\User;
use App\Models\UserActionVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserActionVerificationService
{
    public function send(User $administrator, User $user, string $action, ?string $password = null): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        UserActionVerification::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'admin_id' => $administrator->id,
                'action' => $action,
                'email' => $user->email,
                'pending_password' => $password,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(15),
                'attempts' => 0,
            ],
        );

        Mail::to($user->email)->send(new UserActionVerificationCode($code, $action));
    }

    public function resend(User $administrator, UserActionVerification $verification): void
    {
        if ($verification->admin_id !== $administrator->id) {
            abort(403);
        }

        $this->send(
            $administrator,
            $verification->user,
            $verification->action,
            $verification->pending_password,
        );
    }

    public function verify(
        User $administrator,
        User $user,
        string $code,
        ?string $password = null,
    ): void {
        $error = null;

        DB::transaction(function () use ($administrator, $user, $code, $password, &$error): void {
            $verification = UserActionVerification::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($verification->admin_id !== $administrator->id) {
                abort(403);
            }

            if ($verification->email !== $user->email || $verification->expires_at->isPast()) {
                $error = 'Le code a expiré. Demandez un nouveau code.';

                return;
            }

            if ($verification->attempts >= 5) {
                $error = 'Trop de tentatives. Demandez un nouveau code.';

                return;
            }

            if (! Hash::check($code, $verification->code_hash)) {
                $verification->increment('attempts');
                $error = 'Le code de vérification est invalide.';

                return;
            }

            if ($verification->action === 'password') {
                $newPassword = $password ?? $verification->pending_password;

                if ($newPassword === null) {
                    $error = 'Un nouveau mot de passe est requis.';

                    return;
                }

                $user->password = $newPassword;
                $user->setRememberToken(Str::random(60));
            }

            $user->email_verified_at ??= now();
            $user->save();
            $verification->delete();
        });

        if ($error !== null) {
            throw ValidationException::withMessages(['code' => [$error]]);
        }
    }
}
