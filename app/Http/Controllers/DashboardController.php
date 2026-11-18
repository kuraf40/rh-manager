<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\DisciplinaryAction;
use App\Models\RegisteredHour;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'totalEmployees' => Employee::where('actif', true)->count(),
            'totalHours' => RegisteredHour::sum('total_heures'),
            'totalSanctions' => DisciplinaryAction::count(),
        ]);
    }
}