<?php

namespace App\Console\Commands;

use App\Services\LegacyDataImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('legacy:import {--source=legacy : Source connection name} {--execute : Write records to the application database} {--allow-rejections : Import valid rows even when the audit reports rejected rows}')]
#[Description('Audit or import the historical RH data into the application database.')]
class ImportLegacyData extends Command
{
    public function handle(LegacyDataImporter $importer): int
    {
        $sourceConnection = (string) $this->option('source');

        try {
            $plan = $importer->preview($sourceConnection);
        } catch (Throwable $exception) {
            $this->error('Impossible de lire la source legacy. Vérifiez la connexion et les tables requises.');

            return self::FAILURE;
        }

        $this->table(
            ['Table source', 'Lignes', 'Importables'],
            collect($plan['source_counts'])->map(fn (int $count, string $table): array => [
                $table,
                $count,
                match ($table) {
                    'liste_employes' => count($plan['employees']),
                    'heures_enregistrees' => count($plan['hours']),
                    'parametres_hs' => $plan['parameters'] === null ? 0 : 1,
                    'utilisateurs' => 0,
                    default => count(array_filter($plan['actions'], fn (array $row): bool => $row['source_table'] === $table)),
                },
            ])->values()->all(),
        );

        $this->line('Lignes rejetées: '.count($plan['issues']));
        $this->line('Comptes historiques: non importés automatiquement (identifiants/rôles à rapprocher).');

        if ($plan['issues'] !== []) {
            $this->table(['Table', 'Clé', 'Motif'], array_map(fn (array $issue): array => array_values($issue), array_slice($plan['issues'], 0, 20)));
        }

        if (! $this->option('execute')) {
            $this->info('Audit terminé sans écriture. Relancez avec --execute après vérification.');

            return self::SUCCESS;
        }

        if ($plan['issues'] !== [] && ! $this->option('allow-rejections')) {
            $this->error('Import bloqué: corrigez les rejets ou relancez explicitement avec --allow-rejections.');

            return self::FAILURE;
        }

        try {
            $result = $importer->import($plan);
        } catch (Throwable $exception) {
            $this->error('Import annulé dans une transaction. Vérifiez que les migrations applicatives sont à jour.');

            return self::FAILURE;
        }

        $this->table(['Élément', 'Importé'], collect($result)->map(fn (int $count, string $key): array => [$key, $count])->values()->all());
        $this->info('Import terminé. Les comptes historiques restent à rapprocher manuellement.');

        return self::SUCCESS;
    }
}
