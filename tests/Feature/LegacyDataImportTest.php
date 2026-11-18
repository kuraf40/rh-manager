<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyDataImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $legacyConnection = config('database.connections.sqlite');
        $legacyConnection['database'] = ':memory:';
        config(['database.connections.legacy' => $legacyConnection]);
        DB::purge('legacy');

        $schema = Schema::connection('legacy');
        $schema->create('liste_employes', function ($table): void {
            $table->increments('id')->nullable();
            $table->string('matricule')->nullable();
            $table->string('site')->nullable();
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('naissance')->nullable();
            $table->string('genre')->nullable();
            $table->string('fonction')->nullable();
            $table->string('embauche')->nullable();
            $table->string('poste_specifique')->nullable();
            $table->string('hierachie')->nullable();
            $table->string('categorie')->nullable();
            $table->boolean('actif')->default(true);
        });
        $schema->create('heures_enregistrees', function ($table): void {
            $table->increments('id');
            $table->string('matricule');
            $table->date('date_enreg');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->decimal('total_heures', 5, 2);
            $table->decimal('h_jour', 5, 2)->default(0);
            $table->decimal('h_nuit', 5, 2)->default(0);
            $table->decimal('h_ferie_jour', 5, 2)->default(0);
            $table->decimal('h_ferie_nuit', 5, 2)->default(0);
            $table->string('mois_reference')->nullable();
            $table->integer('semaine_reference')->nullable();
        });

        foreach (['demandes_explication', 'avertissement', 'blame', 'mises_a_pied'] as $tableName) {
            $schema->create($tableName, function ($table): void {
                $table->increments('id');
                $table->string('code')->nullable();
                $table->string('matricule')->nullable();
                $table->text('motif')->nullable();
                $table->text('resume')->nullable();
                $table->integer('nb_jours')->nullable();
                $table->string('attribue_par')->nullable();
                $table->dateTime('date_enregistrement')->nullable();
            });
        }

        $schema->create('parametres_hs', function ($table): void {
            $table->increments('id');
            $table->decimal('hs_12', 5, 2);
            $table->decimal('hs_35', 5, 2);
            $table->decimal('hs_50', 5, 2);
            $table->decimal('hs_100', 5, 2);
        });

        $this->seedLegacyRows();
    }

    public function test_audit_does_not_write_to_the_application_database(): void
    {
        $this->artisan('legacy:import', ['--source' => 'legacy'])
            ->expectsOutputToContain('Audit terminé sans écriture.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('registered_hours', 0);
        $this->assertDatabaseCount('disciplinary_actions', 0);
    }

    public function test_import_preserves_relations_and_can_be_repeated_safely(): void
    {
        $this->artisan('legacy:import', ['--source' => 'legacy', '--execute' => true])
            ->assertExitCode(0);

        $employee = Employee::query()->where('matricule', '51')->firstOrFail();
        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'total_heures' => 4,
        ]);
        $this->assertDatabaseHas('disciplinary_actions', [
            'employee_id' => $employee->id,
            'type' => 'avertissement',
            'code' => '2025-08-0001',
        ]);
        $this->assertDatabaseHas('legacy_import_records', [
            'source_table' => 'heures_enregistrees',
            'source_key' => 'id:44',
        ]);

        $this->artisan('legacy:import', ['--source' => 'legacy', '--execute' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('registered_hours', 1);
        $this->assertDatabaseCount('disciplinary_actions', 1);
        $this->assertDatabaseCount('overtime_parameters', 1);
    }

    public function test_import_refuses_ambiguous_duplicate_employee_matricules_by_default(): void
    {
        DB::connection('legacy')->table('liste_employes')->insert([
            'matricule' => '51',
            'nom' => 'DUPLICATE',
            'prenom' => 'Ambigu',
        ]);

        $this->artisan('legacy:import', ['--source' => 'legacy', '--execute' => true])
            ->expectsOutputToContain('Import bloqué')
            ->assertExitCode(1);

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('registered_hours', 0);
    }

    private function seedLegacyRows(): void
    {
        DB::connection('legacy')->table('liste_employes')->insert([
            'matricule' => '51',
            'site' => 'Cotonou',
            'nom' => 'AGON',
            'prenom' => 'Maurice',
            'naissance' => '01/01/1988',
            'genre' => 'H',
            'fonction' => 'MAGASINIER',
            'embauche' => '01/01/2010',
            'hierachie' => 'Responsable Exploitation',
            'categorie' => 'Agent',
            'actif' => true,
        ]);
        DB::connection('legacy')->table('heures_enregistrees')->insert([
            'id' => 44,
            'matricule' => '51',
            'date_enreg' => '2025-08-15',
            'heure_debut' => '22:00:00',
            'heure_fin' => '02:00:00',
            'total_heures' => 4,
            'h_jour' => 0,
            'h_nuit' => 4,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
            'mois_reference' => '2025-08',
            'semaine_reference' => 33,
        ]);
        DB::connection('legacy')->table('avertissement')->insert([
            'id' => 1,
            'code' => '2025-08-0001',
            'matricule' => '51',
            'motif' => 'Retard',
            'resume' => 'Retard répété',
            'attribue_par' => 'RH',
            'date_enregistrement' => '2025-08-07 11:51:01',
        ]);
        DB::connection('legacy')->table('parametres_hs')->insert([
            'id' => 1,
            'hs_12' => 10,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ]);
    }
}
