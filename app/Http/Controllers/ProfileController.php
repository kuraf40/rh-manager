<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\UserActionVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        UserActionVerificationService $verificationService,
    ): RedirectResponse {
        $user = $request->user();
        $emailChanged = $user->email !== $request->validated('email');

        $user->name = $request->validated('name');

        if ($emailChanged) {
            $user->email = $request->validated('email');
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $verificationService->send($user, $user, 'email');

            return redirect()->route('users.verification', $user)
                ->with('message', 'Un code de vérification a été envoyé par courriel.');
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return Redirect::back()->withErrors(['account' => 'Le dernier administrateur ne peut pas supprimer son compte.']);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
