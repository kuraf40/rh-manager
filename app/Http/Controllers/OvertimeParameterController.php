<?php

namespace App\Http\Controllers;

use App\Models\OvertimeParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeParameterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hours/Settings', [
            'parameters' => OvertimeParameter::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hs_12' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'hs_35' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'hs_50' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'hs_100' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ], [
            'hs_12.max' => 'Le taux ne peut pas dépasser 999,99 %.',
            'hs_35.max' => 'Le taux ne peut pas dépasser 999,99 %.',
            'hs_50.max' => 'Le taux ne peut pas dépasser 999,99 %.',
            'hs_100.max' => 'Le taux ne peut pas dépasser 999,99 %.',
        ]);

        OvertimeParameter::current()->fill($validated)->save();

        return back()->with('message', 'Paramètres de majoration enregistrés.');
    }
}
