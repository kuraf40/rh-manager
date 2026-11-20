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

## Comptes et verification par courriel

- L'inscription publique est desactivee. Seul un administrateur peut creer, modifier ou supprimer les comptes.
- A la creation d'un compte, un code a 6 chiffres est envoye a l'adresse saisie. L'administrateur doit le confirmer pour verifier l'adresse et activer l'acces.
- Tout changement de mot de passe par l'administrateur est applique seulement apres confirmation du code envoye a l'adresse du membre. La recuperation du mot de passe administrateur utilise aussi un code.
- Les codes expirent apres 15 minutes et sont limites a cinq essais. Les membres non administrateurs n'ont pas de profil et ne peuvent pas modifier leur mot de passe.
- Le logo Ubipharm fourni est utilise sur l'ecran de connexion et dans la navigation de l'application.

La configuration locale utilise `MAIL_MAILER=log` par defaut: les courriels ne sont alors pas remis aux boites de reception. Pour envoyer les codes en reel, configurez un serveur SMTP autorise dans `.env`, par exemple:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.votre-fournisseur.example
MAIL_PORT=587
MAIL_USERNAME=votre-identifiant
MAIL_PASSWORD=votre-mot-de-passe-smtp
MAIL_FROM_ADDRESS=adresse-expediteur@votre-domaine.example
MAIL_FROM_NAME="${APP_NAME}"
```

Utilisez les identifiants SMTP fournis par votre organisation, ne les commitez pas, puis executez `php artisan config:clear`. Avant toute utilisation, remplacez egalement `admin@example.com` par une adresse administrateur valide pouvant recevoir les codes.

## Fonctionnalités livrées

- Authentification, profil réservé à l'administrateur et administration des comptes; seuls les rôles Admin, RH et Manager peuvent être attribués, et l'inscription publique est désactivée.
- Employés: recherche, pagination, gestion des champs historiques, modification, désactivation/réactivation sans effacer l'historique et anniversaires à venir.
- Heures supplémentaires: saisie réservée aux magasiniers actifs, date non future, vacation, horaires par tranches de 30 minutes et choix manuel du jour férié. Les créneaux de pause/hors vacation sont contrôlés; la durée et les catégories jour/nuit/férié sont calculées automatiquement. Les saisies traversant minuit sont séparées par date. L'historique est paginé et recherchable par employé, matricule, date et horaires; les responsables autorisés peuvent corriger ou supprimer une saisie; une modification ne peut pas créer un nouveau segment traversant minuit.
- Plafond hebdomadaire historique de 5 h: avertissement avec confirmation explicite, calculé sur les semaines du cycle de référence du 20 au 19. Le rapport hebdomadaire suit la semaine lundi-dimanche; le rapport mensuel suit le cycle du 20 au 19.
- Rapports hebdomadaires et mensuels agrégés par employé; le rapport mensuel suit le cycle legacy du 20 au 19 et répartit les heures dans les quatre paliers HS configurables. Chaque rapport offre un retour à la saisie, l'impression et le téléchargement CSV avec la période exacte dans le titre imprimé.
- Discipline: quatre types d'action, historique daté recherchable par employé, matricule, type, motif ou date et statistiques par employé avec recherche par nom, prénom, matricule ou fonction; les responsables RH peuvent modifier ou supprimer une action. Le champ « code » n'est plus demandé lors de la saisie; les valeurs historiques importées restent conservées.
- Tableau de bord: indicateurs des employés actifs, des heures supplémentaires et des actions disciplinaires, avec graphiques des heures par mois (six derniers mois) et des sanctions par type.
- Les enregistrements réussis (heures, taux et actions disciplinaires) affichent une notification temporaire de confirmation.
- Accès: Admin voit tous les onglets; RH voit les mêmes onglets sauf Comptes; Manager voit les onglets sauf Comptes, Taux et Anniversaires. Les routes appliquent les mêmes permissions côté serveur.

Répartition des taux héritée de `projetstage`: HS_12 = les 8 premières heures de jour du cycle; HS_35 = les heures de jour au-delà de 8; HS_50 = heures de nuit plus heures de jour fériées; HS_100 = heures de nuit fériées. Les taux configurés sont affichés sur ces catégories; aucun montant salarial n'est calculé, car aucune base de salaire n'existe dans ce workflow. Le cycle mensuel couvre le 20 du mois précédent au 19 du mois sélectionné. Le type férié est déclaré manuellement à la saisie; aucun calendrier férié n'est inventé. `registered_hours.vacation` et `registered_hours.type_jour` conservent les informations de la saisie; ces colonnes sont nulles pour les anciennes heures importées. Les tables `overtime_settings`, `timeslots` et `overtimes` sont un schéma préexistant non utilisé; `registered_hours` et `overtime_parameters` sont les modèles actifs.

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
- Confirmer avec les RH les plages de pause/hors vacation et le plafond hebdomadaire historique de 5 h avant de les utiliser comme règle de paie.
- Confirmer les règles métier locales des majorations et jours fériés avant d'utiliser les rapports pour la paie.
- Décider si `timeslots/overtimes` sera supprimé par une migration future ou développé comme workflow séparé.

## Tests et repères

La suite a passé **45 tests / 169 assertions** le 29 septembre 2026. Sur MAMP, si `pdo_sqlite` n'est pas activé globalement:

```powershell
php -d extension_dir=C:\MAMP\bin\php\php8.3.1\ext -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit
npm run build
php artisan route:list
```

Vérification locale du 20 novembre 2026: le build frontend et les tests ciblés des sanctions, heures et tableau de bord passent (**26 tests / 205 assertions**). La suite complète compte **78 tests / 439 assertions**; 77 passent. L'unique échec est le test préexistant `InertiaSharedPropsTest::test_example`, qui attend un `200` sur `/` alors que la route redirige vers la connexion (`302`).

- `routes/web.php`: routes et permissions.
- `app/Http/Controllers`: traitements RH.
- `app/Services/LegacyDataImporter.php`: audit et import transactionnel.
- `database/migrations`: schéma et suivi de reprise.
- `resources/js/Pages`: interface Vue/Inertia.