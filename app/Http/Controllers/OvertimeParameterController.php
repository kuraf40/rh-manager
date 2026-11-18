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
            'parameters' => $this->parameters(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hs_12' => ['required', 'numeric', 'min:0', 'max:1000'],
            'hs_35' => ['required', 'numeric', 'min:0', 'max:1000'],
            'hs_50' => ['required', 'numeric', 'min:0', 'max:1000'],
            'hs_100' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $this->parameters()->fill($validated)->save();

        return back()->with('message', 'Paramètres de majoration enregistrés.');
    }

    private function parameters(): OvertimeParameter
    {
        return OvertimeParameter::query()->find(1) ?? OvertimeParameter::query()->create([
            'hs_12' => 10,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ]);
    }
}
