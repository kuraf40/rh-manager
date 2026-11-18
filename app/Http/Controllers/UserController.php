<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const ROLES = ['admin', 'rh', 'manager', 'employee'];

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->with('employee:id,user_id,matricule,nom,prenom')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'employees' => $this->availableEmployees(),
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:12'],
            'role' => ['required', Rule::in(self::ROLES)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'email_verified_at' => now(),
            ]);

            $this->syncEmployee($user, $validated['employee_id'] ?? null);

            return $user;
        });

        return redirect()->route('users.edit', $user)->with('message', 'Compte créé.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->load('employee:id,user_id,matricule,nom,prenom'),
            'employees' => $this->availableEmployees($user),
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:12'],
            'role' => ['required', Rule::in(self::ROLES)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        if ($user->role === 'admin' && $validated['role'] !== 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Le dernier administrateur ne peut pas être rétrogradé.']);
        }

        DB::transaction(function () use ($validated, $user): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            if (($validated['password'] ?? '') !== '') {
                $user->password = Hash::make($validated['password']);
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = now();
            }

            $user->save();
            $this->syncEmployee($user, $validated['employee_id'] ?? null);
        });

        return back()->with('message', 'Compte mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->getKey()) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer le compte actuellement connecté.']);
        }

        if ($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'Le dernier administrateur ne peut pas être supprimé.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('message', 'Compte supprimé.');
    }

    private function availableEmployees(?User $user = null): array
    {
        return Employee::query()
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');

                if ($user !== null) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('nom')
            ->get(['id', 'user_id', 'matricule', 'nom', 'prenom'])
            ->toArray();
    }

    private function syncEmployee(User $user, ?int $employeeId): void
    {
        if ($user->employee()->exists()) {
            $user->employee()->update(['user_id' => null]);
        }

        if ($employeeId === null) {
            return;
        }

        $employee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

        if ($employee->user_id !== null && $employee->user_id !== $user->id) {
            abort(409, 'Cet employé est déjà rattaché à un autre compte.');
        }

        $employee->update(['user_id' => $user->id]);
    }
}
