<?php

namespace App\Services;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use App\Models\OvertimeParameter;
use App\Models\RegisteredHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LegacyDataImporter
{
    private const SANCTION_TABLES = [
        'demandes_explication' => 'demande_explication',
        'avertissement' => 'avertissement',
        'blame' => 'blame',
        'mises_a_pied' => 'mise_a_pied',
    ];

    public function preview(string $sourceConnection): array
    {
        $source = DB::connection($sourceConnection);
        $sourceSchema = Schema::connection($sourceConnection);
        $requiredTables = [
            'liste_employes',
            'heures_enregistrees',
            ...array_keys(self::SANCTION_TABLES),
            'parametres_hs',
        ];

        foreach ($requiredTables as $table) {
            if (! $sourceSchema->hasTable($table)) {
                throw new InvalidArgumentException("La table legacy '{$table}' est absente.");
            }
        }

        $issues = [];
        $employeesByMatricule = [];
        $employeeRows = $source->table('liste_employes')->get()->map(fn (object $row): array => (array) $row)->all();

        foreach ($employeeRows as $row) {
            $matricule = $this->normalizeMatricule($row['matricule'] ?? null);

            if ($matricule === null) {
                $issues[] = ['table' => 'liste_employes', 'key' => $row['id'] ?? 'inconnu', 'reason' => 'matricule absent'];

                continue;
            }

            $employeesByMatricule[$matricule][] = $row;
        }

        $importableEmployees = [];
        $targetEmployeeIds = Employee::query()->pluck('id', 'matricule')->all();

        foreach ($employeesByMatricule as $matricule => $rows) {
            if (count($rows) !== 1) {
                $issues[] = ['table' => 'liste_employes', 'key' => $matricule, 'reason' => 'matricule dupliqué'];

                continue;
            }

            $row = $rows[0];
            $birthDate = $this->normalizeDate($row['naissance'] ?? null);
            $hireDate = $this->normalizeDate($row['embauche'] ?? null);

            if (trim((string) ($row['nom'] ?? '')) === '' || trim((string) ($row['prenom'] ?? '')) === '') {
                $issues[] = ['table' => 'liste_employes', 'key' => $matricule, 'reason' => 'nom ou prénom absent'];

                continue;
            }

            if (! empty($row['naissance']) && $birthDate === null) {
                $issues[] = ['table' => 'liste_employes', 'key' => $matricule, 'reason' => 'date de naissance invalide'];

                continue;
            }

            if (! empty($row['embauche']) && $hireDate === null) {
                $issues[] = ['table' => 'liste_employes', 'key' => $matricule, 'reason' => 'date d’embauche invalide'];

                continue;
            }

            $sourceKey = isset($row['id']) ? 'id:'.$row['id'] : 'matricule:'.$matricule;
            $importableEmployees[] = [
                'source_key' => $sourceKey,
                'matricule' => $matricule,
                'target_id' => $targetEmployeeIds[$matricule] ?? null,
                'attributes' => [
                    'matricule' => $matricule,
                    'site' => $row['site'] ?: 'Cotonou',
                    'nom' => trim((string) ($row['nom'] ?? '')),
                    'prenom' => trim((string) ($row['prenom'] ?? '')),
                    'naissance' => $birthDate,
                    'genre' => in_array($row['genre'] ?? null, ['H', 'F'], true) ? $row['genre'] : 'H',
                    'fonction' => $row['fonction'] ?: null,
                    'embauche' => $hireDate,
                    'poste_specifique' => $row['poste_specifique'] ?: null,
                    'hierarchie' => $row['hierachie'] ?? null,
                    'categorie' => $row['categorie'] ?: null,
                    'actif' => (bool) ($row['actif'] ?? true),
                ],
            ];
        }

        $availableMatricules = array_fill_keys(array_column($importableEmployees, 'matricule'), true);
        $availableMatricules += array_fill_keys(array_keys($targetEmployeeIds), true);
        $importableHours = [];
        $hourRows = $source->table('heures_enregistrees')->get()->map(fn (object $row): array => (array) $row)->all();

        foreach ($hourRows as $row) {
            $matricule = $this->normalizeMatricule($row['matricule'] ?? null);

            if (! isset($row['id']) || $matricule === null || ! isset($availableMatricules[$matricule])) {
                $issues[] = ['table' => 'heures_enregistrees', 'key' => $row['id'] ?? 'inconnu', 'reason' => 'identifiant ou employé introuvable'];

                continue;
            }

            $date = $this->normalizeDate($row['date_enreg'] ?? null);

            $hourCategories = [
                $row['h_jour'] ?? 0,
                $row['h_nuit'] ?? 0,
                $row['h_ferie_jour'] ?? 0,
                $row['h_ferie_nuit'] ?? 0,
            ];

            if (
                $date === null ||
                ! is_numeric($row['total_heures'] ?? null) ||
                (float) $row['total_heures'] < 0 ||
                empty($row['heure_debut']) ||
                empty($row['heure_fin']) ||
                collect($hourCategories)->contains(fn (mixed $hours): bool => ! is_numeric($hours) || (float) $hours < 0)
            ) {
                $issues[] = ['table' => 'heures_enregistrees', 'key' => $row['id'], 'reason' => 'date ou durée invalide'];

                continue;
            }

            $importableHours[] = ['source_key' => 'id:'.$row['id'], 'matricule' => $matricule, 'attributes' => [
                'date_enreg' => $date,
                'heure_debut' => $row['heure_debut'],
                'heure_fin' => $row['heure_fin'],
                'total_heures' => $row['total_heures'],
                'h_jour' => $row['h_jour'] ?? 0,
                'h_nuit' => $row['h_nuit'] ?? 0,
                'h_ferie_jour' => $row['h_ferie_jour'] ?? 0,
                'h_ferie_nuit' => $row['h_ferie_nuit'] ?? 0,
                'mois_reference' => $row['mois_reference'] ?? null,
                'semaine_reference' => $row['semaine_reference'] ?? null,
            ]];
        }

        $importableActions = [];

        foreach (self::SANCTION_TABLES as $table => $type) {
            $rows = $source->table($table)->get()->map(fn (object $row): array => (array) $row)->all();

            foreach ($rows as $row) {
                $matricule = $this->normalizeMatricule($row['matricule'] ?? null);

                if (! isset($row['id']) || $matricule === null || ! isset($availableMatricules[$matricule])) {
                    $issues[] = ['table' => $table, 'key' => $row['id'] ?? 'inconnu', 'reason' => 'identifiant ou employé introuvable'];

                    continue;
                }

                $importableActions[] = ['source_table' => $table, 'source_key' => 'id:'.$row['id'], 'matricule' => $matricule, 'attributes' => [
                    'type' => $type,
                    'code' => $row['code'] ?? null,
                    'motif' => $row['motif'] ?? null,
                    'resume' => $row['resume'] ?? null,
                    'nb_jours' => $row['nb_jours'] ?? null,
                    'attribue_par' => $row['attribue_par'] ?? null,
                    'date_enregistrement' => $row['date_enregistrement'] ?? now(),
                ]];
            }
        }

        $parameters = $source->table('parametres_hs')->orderBy('id')->first();

        if ($parameters === null) {
            $issues[] = ['table' => 'parametres_hs', 'key' => 'aucune', 'reason' => 'paramètres absents'];
        }

        return [
            'employees' => $importableEmployees,
            'hours' => $importableHours,
            'actions' => $importableActions,
            'parameters' => $parameters ? (array) $parameters : null,
            'issues' => $issues,
            'source_counts' => [
                'liste_employes' => count($employeeRows),
                'heures_enregistrees' => count($hourRows),
                ...collect(self::SANCTION_TABLES)->mapWithKeys(fn (string $type, string $table): array => [$table => $source->table($table)->count()])->all(),
                'parametres_hs' => $source->table('parametres_hs')->count(),
                'utilisateurs' => $sourceSchema->hasTable('utilisateurs') ? $source->table('utilisateurs')->count() : 0,
            ],
        ];
    }

    public function import(array $plan): array
    {
        if (! Schema::hasTable('legacy_import_records')) {
            throw new InvalidArgumentException('La table de suivi manque; exécutez les migrations avant l’import.');
        }

        return DB::transaction(function () use ($plan): array {
            $counts = ['employees' => 0, 'hours' => 0, 'actions' => 0, 'parameters' => 0, 'already_imported' => 0];
            $employeeIds = [];

            foreach ($plan['employees'] as $row) {
                $existingRecord = $this->trackedTarget('liste_employes', $row['source_key']);

                if ($existingRecord !== null) {
                    $employeeIds[$row['matricule']] = (int) $existingRecord->target_id;
                    $counts['already_imported']++;

                    continue;
                }

                $employee = Employee::query()->where('matricule', $row['matricule'])->first();

                if ($employee === null) {
                    $employee = Employee::create($row['attributes']);
                    $counts['employees']++;
                }

                $employeeIds[$row['matricule']] = (int) $employee->getKey();
                $this->recordImport('liste_employes', $row['source_key'], 'employees', (int) $employee->getKey());
            }

            foreach ($plan['hours'] as $row) {
                if ($this->trackedTarget('heures_enregistrees', $row['source_key']) !== null) {
                    $counts['already_imported']++;

                    continue;
                }

                $employeeId = $employeeIds[$row['matricule']] ?? Employee::query()->where('matricule', $row['matricule'])->value('id');

                if ($employeeId === null) {
                    continue;
                }

                $hour = RegisteredHour::create(['employee_id' => $employeeId, ...$row['attributes']]);
                $this->recordImport('heures_enregistrees', $row['source_key'], 'registered_hours', (int) $hour->getKey());
                $counts['hours']++;
            }

            foreach ($plan['actions'] as $row) {
                if ($this->trackedTarget($row['source_table'], $row['source_key']) !== null) {
                    $counts['already_imported']++;

                    continue;
                }

                $employeeId = $employeeIds[$row['matricule']] ?? Employee::query()->where('matricule', $row['matricule'])->value('id');

                if ($employeeId === null) {
                    continue;
                }

                $action = DisciplinaryAction::create(['employee_id' => $employeeId, ...$row['attributes']]);
                $this->recordImport($row['source_table'], $row['source_key'], 'disciplinary_actions', (int) $action->getKey());
                $counts['actions']++;
            }

            if ($plan['parameters'] !== null) {
                $sourceKey = 'id:'.($plan['parameters']['id'] ?? '1');

                if ($this->trackedTarget('parametres_hs', $sourceKey) === null) {
                    $parameters = OvertimeParameter::query()->find($plan['parameters']['id'] ?? 1)
                        ?? OvertimeParameter::query()->firstOrNew();
                    $parameters->fill([
                        'hs_12' => $plan['parameters']['hs_12'],
                        'hs_35' => $plan['parameters']['hs_35'],
                        'hs_50' => $plan['parameters']['hs_50'],
                        'hs_100' => $plan['parameters']['hs_100'],
                    ])->save();
                    $this->recordImport('parametres_hs', $sourceKey, 'overtime_parameters', (int) $parameters->getKey());
                    $counts['parameters']++;
                } else {
                    $counts['already_imported']++;
                }
            }

            return $counts;
        });
    }

    private function trackedTarget(string $sourceTable, string $sourceKey): ?object
    {
        return DB::table('legacy_import_records')
            ->where('source_table', $sourceTable)
            ->where('source_key', $sourceKey)
            ->first();
    }

    private function recordImport(string $sourceTable, string $sourceKey, string $targetTable, int $targetId): void
    {
        DB::table('legacy_import_records')->insert([
            'source_table' => $sourceTable,
            'source_key' => $sourceKey,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeMatricule(mixed $matricule): ?string
    {
        $matricule = trim((string) $matricule);

        return $matricule === '' ? null : $matricule;
    }

    private function normalizeDate(mixed $date): ?string
    {
        if ($date === null || trim((string) $date) === '') {
            return null;
        }

        foreach (['!Y-m-d', '!d/m/Y', '!Y-m-d H:i:s'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, (string) $date);
            } catch (\Throwable) {
                $parsed = false;
            }

            if ($parsed !== false) {
                return $parsed->toDateString();
            }
        }

        return null;
    }
}
