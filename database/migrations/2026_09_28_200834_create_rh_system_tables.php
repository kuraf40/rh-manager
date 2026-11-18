<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Table des employés (remplace liste_employes)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 20)->unique();
            $table->string('site', 50)->default('Cotonou');
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('naissance', 20)->nullable();
            $table->char('genre', 1)->default('H');
            $table->string('fonction', 150)->nullable();
            $table->string('embauche', 20)->nullable();
            $table->string('poste_specifique', 150)->nullable();
            $table->string('hierarchie', 150)->nullable();
            $table->string('categorie', 50)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // 2. Table des paramètres HS (remplace parametres_hs)
        Schema::create('overtime_parameters', function (Blueprint $table) {
            $table->id();
            $table->decimal('hs_12', 5, 2)->default(10.00);
            $table->decimal('hs_35', 5, 2)->default(35.00);
            $table->decimal('hs_50', 5, 2)->default(50.00);
            $table->decimal('hs_100', 5, 2)->default(100.00);
            $table->timestamps();
        });

        // 3. Table des heures enregistrées (remplace heures_enregistrees)
        Schema::create('registered_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date_enreg');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->decimal('total_heures', 5, 2);
            $table->decimal('h_jour', 5, 2)->default(0.00);
            $table->decimal('h_nuit', 5, 2)->default(0.00);
            $table->decimal('h_ferie_jour', 5, 2)->default(0.00);
            $table->decimal('h_ferie_nuit', 5, 2)->default(0.00);
            $table->string('mois_reference', 7)->nullable();
            $table->integer('semaine_reference')->nullable();
            $table->timestamps();
        });

        // 4. Table unique des Sanctions Disciplinaires (Avertissement, Blâme, Demande d'explication, Mise à pied)
        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['demande_explication', 'avertissement', 'blame', 'mise_a_pied']);
            $table->string('code', 20)->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('motif')->nullable();
            $table->text('resume')->nullable();
            $table->integer('nb_jours')->nullable(); // Pour les mises à pied
            $table->string('attribue_par', 100)->nullable();
            $table->timestamp('date_enregistrement')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_actions');
        Schema::dropIfExists('registered_hours');
        Schema::dropIfExists('overtime_parameters');
        Schema::dropIfExists('employees');
    }
};