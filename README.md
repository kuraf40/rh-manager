# RH Manager

Modernisation de l'ancienne application PHP/MySQL fournie dans `projetstage.zip`, avec le dump `ubi.sql`. L'application Laravel/Vue reprend ses workflows RH et conserve ce README comme mémoire évolutive du projet. Mettre à jour l'état, les décisions et les points manuels après chaque évolution.

## Technologies

- PHP 8.3 et Laravel 13
- Vue 3, Inertia.js 2 et Vite
- MySQL pour l'environnement courant; SQLite est configuré pour les tests
- PHPUnit

## Bases de données

- **Cible applicative**: `aravel`. Le `.env` local et `.env.example` ciblent cette base; l'ancienne base `laravel` n'a pas été modifiée.
- **Source importée**: `ubi_legacy_stage_20260929`, staging créé à partir du dump fourni. La base `ubi` préexistante a été laissée intacte, car un tablespace InnoDB orphelin empêchait la création des tables.
- Les identifiants proviennent de `DB_*`; les options `LEGACY_DB_*` peuvent remplacer la source. Ne jamais committer `.env` ni ses secrets.

Le schéma `aravel` a reçu les migrations, le compte admin local, puis l'import historique validé.

## Démarrage local

Prérequis: PHP 8.3, Composer, Node.js, npm et `pdo_mysql`. Depuis PowerShell, à la racine du projet:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Le seeder crée le compte local `admin@example.com` avec le mot de passe initial `password` et les taux par défaut. Change ce mot de passe avant toute exposition hors poste local. Vite en mode développement se lance dans un autre terminal avec `npm run dev`.

## Fonctionnalités livrées

- Authentification, profil et administration admin des comptes; inscription publique désactivée.
- Employés: recherche, pagination, gestion des champs historiques, modification, désactivation/réactivation sans effacer l'historique et anniversaires à venir.
- Pointages: durée nette à partir des heures de début/fin et pauses, postes traversant minuit, validation de la ventilation jour/nuit/férié.
- Rapports hebdomadaires et mensuels agrégés par employé; paramètres des taux historiques configurables.
- Discipline: quatre types d'action, historique daté et statistiques par employé.
- Accès: employés/discipline admin-RH; saisie heures admin-RH-manager; rapports et taux admin-RH; comptes admin seulement.

Les taux ne calculent pas de montant salarial. Les catégories horaires sont déclarées par le gestionnaire, sans inventer de calendrier férié. Les tables `overtime_settings`, `timeslots` et `overtimes` sont un schéma préexistant non utilisé; `registered_hours` et `overtime_parameters` sont les modèles actifs.

## Reprise legacy

La commande `legacy:import` fait un audit sans écriture par défaut:

```powershell
php artisan legacy:import --source=legacy
```

`--execute` lance un import idempotent dans une transaction. Les rejets bloquent par défaut; `--allow-rejections` doit être utilisé explicitement après examen:

```powershell
php artisan legacy:import --source=legacy --execute --allow-rejections
```

L'import reprend employés, heures, quatre tables disciplinaires et taux. Heures/actions sont reliées par matricule et tracées dans `legacy_import_records`. Les comptes legacy ne sont jamais créés automatiquement; leur identité, leur rôle et leur nouveau mot de passe doivent être rapprochés manuellement.

### Bilan du 29 septembre 2026

- Importés dans `aravel`: 113 employés, 11 pointages, 15 actions disciplinaires et 1 configuration de taux.
- 140 lignes suivies; aucun pointage ni action orphelin.
- Rejet: matricule `5533` dupliqué dans le dump; aucune des lignes ambiguës n'a été choisie.
- Les 4 comptes legacy restent exclus et à rapprocher/créer via l'interface admin.
- Le dump a été chargé dans `ubi_legacy_stage_20260929`. Les bases préexistantes `ubi` et `laravel` n'ont pas été modifiées.

## Points encore ouverts

- Résoudre manuellement le doublon `5533`; relancer l'audit puis importer la ligne correcte si elle est identifiée.
- Rapprocher les 4 anciens comptes, convertir leurs rôles et réinitialiser leurs mots de passe avant de les recréer.
- Confirmer les règles métier locales des majorations et jours fériés avant d'utiliser les rapports pour la paie.
- Décider si `timeslots/overtimes` sera supprimé par une migration future ou développé comme workflow séparé.

## Tests et repères

La suite a passé **45 tests / 169 assertions** le 29 septembre 2026. Sur MAMP, si `pdo_sqlite` n'est pas activé globalement:

```powershell
php -d extension_dir=C:\MAMP\bin\php\php8.3.1\ext -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit
npm run build
php artisan route:list
```

- `routes/web.php`: routes et permissions.
- `app/Http/Controllers`: traitements RH.
- `app/Services/LegacyDataImporter.php`: audit et import transactionnel.
- `database/migrations`: schéma et suivi de reprise.
- `resources/js/Pages`: interface Vue/Inertia.