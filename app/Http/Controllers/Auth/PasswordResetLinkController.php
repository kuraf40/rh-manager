<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserActionVerificationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        if ($request->boolean('new')) {
            $request->session()->forget('password_reset_email');
        }

        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'email' => session('password_reset_email'),
        ]);
    }

    public function store(Request $request, UserActionVerificationService $verificationService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $administrator = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->first();

        if ($administrator !== null) {
            $verificationService->send($administrator, $administrator, 'password');
        }

        $request->session()->put('password_reset_email', $validated['email']);

        return back()->with('status', 'Si cette adresse correspond à un compte administrateur, un code de vérification a été envoyé.');
    }

    public function verify(Request $request, UserActionVerificationService $verificationService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:12'],
        ]);

        $administrator = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->first();

        if ($administrator === null) {
            throw ValidationException::withMessages([
                'code' => ['Le code de vérification est invalide ou expiré.'],
            ]);
        }

        $verificationService->verify(
            $administrator,
            $administrator,
            $validated['code'],
            $validated['password'],
        );

        Event::dispatch(new PasswordReset($administrator));
        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', 'Le mot de passe administrateur a été modifié.');
    }
}
