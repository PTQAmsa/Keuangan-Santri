<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyalin seluruh isi tabel aplikasi dari satu koneksi database ke koneksi
 * lain, mis. dari SQLite di laptop ke PostgreSQL (Supabase) saat hosting.
 *
 * Tabel disalin berurutan mengikuti relasi antar tabel (tabel induk lebih
 * dulu) sehingga foreign key di tujuan tidak melanggar. Setelah selesai,
 * penomoran otomatis (sequence) PostgreSQL disetel ulang ke angka terakhir.
 */
class SalinDatabase extends Command
{
    protected $signature = 'db:salin
        {--dari=sqlite : Nama koneksi sumber di config/database.php}
        {--ke=pgsql : Nama koneksi tujuan di config/database.php}
        {--ganti : Kosongkan tabel tujuan terlebih dahulu bila sudah berisi}';

    protected $description = 'Salin seluruh data aplikasi dari satu koneksi database ke koneksi lain (mis. SQLite ke Supabase)';

    /**
     * Tabel sistem yang tidak perlu ikut pindah.
     *
     * @var list<string>
     */
    private const SKIP = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
        'sqlite_sequence',
    ];

    public function handle(): int
    {
        $from = (string) $this->option('dari');
        $to = (string) $this->option('ke');

        if ($from === $to) {
            $this->error('Koneksi sumber dan tujuan tidak boleh sama.');

            return self::FAILURE;
        }

        foreach ([$from, $to] as $connection) {
            if (config("database.connections.$connection") === null) {
                $this->error("Koneksi '$connection' tidak ada di config/database.php.");

                return self::FAILURE;
            }
        }

        try {
            $tables = $this->orderedTables($from);
        } catch (\Throwable $e) {
            $this->error('Gagal membaca database sumber: '.$e->getMessage());

            return self::FAILURE;
        }

        $missing = array_values(array_filter($tables, fn (string $table): bool => ! Schema::connection($to)->hasTable($table)));

        if ($missing !== []) {
            $this->error('Tabel berikut belum ada di tujuan, jalankan `php artisan migrate --database='.$to.'` dulu: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $filled = array_values(array_filter($tables, fn (string $table): bool => DB::connection($to)->table($table)->exists()));

        if ($filled !== [] && ! $this->option('ganti')) {
            $this->error('Tabel tujuan sudah berisi data: '.implode(', ', $filled).'. Jalankan dengan --ganti untuk menimpanya.');

            return self::FAILURE;
        }

        if ($filled !== []) {
            foreach (array_reverse($tables) as $table) {
                DB::connection($to)->table($table)->delete();
            }

            $this->warn('Isi lama '.count($filled).' tabel tujuan dihapus.');
        }

        $rows = [];

        foreach ($tables as $table) {
            $rows[$table] = $this->copyTable($from, $to, $table);
        }

        $this->resetSequences($to, $tables);

        $this->table(
            ['Tabel', 'Baris disalin'],
            array_map(fn (string $table): array => [$table, number_format($rows[$table])], $tables)
        );

        $this->info(sprintf('Selesai: %s baris dari %d tabel disalin dari koneksi %s ke %s.', number_format(array_sum($rows)), count($tables), $from, $to));

        return self::SUCCESS;
    }

    /**
     * Daftar tabel aplikasi di sumber, diurutkan agar tabel yang dirujuk
     * foreign key selalu disalin lebih dulu.
     *
     * @return list<string>
     */
    private function orderedTables(string $connection): array
    {
        $schema = Schema::connection($connection);
        $tables = array_values(array_diff($schema->getTableListing(schemaQualified: false), self::SKIP));

        $dependencies = [];

        foreach ($tables as $table) {
            $dependencies[$table] = [];

            foreach ($schema->getForeignKeys($table) as $foreignKey) {
                $parent = $foreignKey['foreign_table'];

                if ($parent !== $table && in_array($parent, $tables, true)) {
                    $dependencies[$table][] = $parent;
                }
            }
        }

        $ordered = [];
        $visiting = [];

        $visit = function (string $table) use (&$visit, &$ordered, &$visiting, $dependencies): void {
            if (in_array($table, $ordered, true)) {
                return;
            }

            if (isset($visiting[$table])) {
                throw new \RuntimeException("Relasi antar tabel melingkar pada tabel $table.");
            }

            $visiting[$table] = true;

            foreach ($dependencies[$table] as $parent) {
                $visit($parent);
            }

            unset($visiting[$table]);
            $ordered[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $ordered;
    }

    private function copyTable(string $from, string $to, string $table): int
    {
        $copied = 0;
        $booleanColumns = $this->booleanColumns($to, $table);

        DB::connection($from)->table($table)->orderBy($this->orderColumn($from, $table))->chunk(200, function ($chunk) use ($to, $table, $booleanColumns, &$copied): void {
            $rows = $chunk->map(function (object $row) use ($booleanColumns): array {
                $data = (array) $row;

                // SQLite menyimpan boolean sebagai 0/1; PostgreSQL menolak angka untuk kolom boolean.
                foreach ($booleanColumns as $column) {
                    if (array_key_exists($column, $data) && $data[$column] !== null) {
                        $data[$column] = (bool) $data[$column];
                    }
                }

                return $data;
            })->all();

            DB::connection($to)->table($table)->insert($rows);
            $copied += count($rows);
        });

        return $copied;
    }

    private function orderColumn(string $connection, string $table): string
    {
        return Schema::connection($connection)->hasColumn($table, 'id') ? 'id' : Schema::connection($connection)->getColumnListing($table)[0];
    }

    /**
     * @return list<string>
     */
    private function booleanColumns(string $connection, string $table): array
    {
        return array_values(array_map(
            fn (array $column): string => $column['name'],
            array_filter(
                Schema::connection($connection)->getColumns($table),
                fn (array $column): bool => in_array(strtolower($column['type_name']), ['bool', 'boolean'], true)
            )
        ));
    }

    /**
     * Setelah menyisipkan baris dengan id eksplisit, sequence PostgreSQL
     * harus disetel ulang agar baris baru tidak bentrok dengan id lama.
     *
     * @param  list<string>  $tables
     */
    private function resetSequences(string $connection, array $tables): void
    {
        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($tables as $table) {
            if (! Schema::connection($connection)->hasColumn($table, 'id')) {
                continue;
            }

            DB::connection($connection)->statement(
                "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM \"$table\"), 0) + 1, false)",
                [$table]
            );
        }
    }
}
