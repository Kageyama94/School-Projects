<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

#[Signature('jo:export-sql {--path= : Fichier de destination (database/jo.sql par défaut)}')]
#[Description('Exporte la base SQLite (structure et données) en un script SQL réimportable avec sqlite3')]
class ExportSqlCommand extends Command
{
    /** Jetons de réinitialisation de mot de passe : temporaires et sensibles, exportés vides. */
    private const TRANSIENT_TABLES = ['password_reset_tokens'];

    private const ROWS_PER_INSERT = 50;

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            $this->error('Seule une base SQLite peut être exportée.');

            return self::FAILURE;
        }

        $objects = collect($connection->select(
            "SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY rowid"
        ));
        $tables = $objects->where('type', 'table')->pluck('name');

        $lines = [
            '-- Base de données SQLite du site Jeux Olympiques (Laravel), générée par php artisan jo:export-sql',
            '-- Données de démonstration : pays réels, athlètes et résultats fictifs.',
            '-- Comptes : admin@jo.test / password (admin), spectateur@jo.test / password',
            '-- Import : sqlite3 database/database.sqlite < database/jo.sql',
            '',
            'PRAGMA foreign_keys = OFF;',
            'BEGIN TRANSACTION;',
            '',
            ...$tables->reverse()->map(fn (string $table) => "DROP TABLE IF EXISTS \"$table\";"),
            '',
            '-- ---------------------------------------------------------------- Structure',
            '',
            ...$objects->map(fn (object $object) => $object->sql.';'),
            '',
            '-- ---------------------------------------------------------------- Données',
        ];

        $rowCount = 0;
        foreach ($tables->diff(self::TRANSIENT_TABLES) as $table) {
            $rows = $connection->table($table)->orderByRaw('rowid')->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $rowCount += $rows->count();
            $columns = '"'.implode('", "', array_keys((array) $rows->first())).'"';
            $lines[] = '';
            $lines[] = "-- $table ({$rows->count()} lignes)";

            foreach ($rows->chunk(self::ROWS_PER_INSERT) as $chunk) {
                $values = $chunk->map(fn (object $row) => '('.collect((array) $row)
                    ->map(fn (mixed $value) => $this->toSql($connection, $value))
                    ->implode(', ').')');
                $lines[] = "INSERT INTO \"$table\" ($columns) VALUES\n".$values->implode(",\n").';';
            }
        }

        $lines = [...$lines, '', 'COMMIT;', 'PRAGMA foreign_keys = ON;', ''];

        $path = $this->option('path') ?? database_path('jo.sql');
        file_put_contents($path, implode("\n", $lines));

        $this->info("{$tables->count()} tables et $rowCount lignes exportées dans $path");

        return self::SUCCESS;
    }

    private function toSql(Connection $connection, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_float($value) => (string) $value,
            default => $connection->getPdo()->quote((string) $value),
        };
    }
}
