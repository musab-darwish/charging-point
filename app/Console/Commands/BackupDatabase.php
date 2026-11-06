<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Export the database to a compressed file in the backup folder (a Google
 * Drive folder, so it leaves the laptop), one file a day, keeping the last
 * few. Run nightly and at startup: a day already backed up is skipped.
 */
#[Signature('backup:database {--force : Back up again even if today already has a copy}')]
#[Description('Back up the database to the backup folder (one copy a day)')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $folder = config('daftar.backup.path');
        $connection = config('database.connections.'.(config('daftar.backup.connection') ?: config('database.default')));

        if (! $folder) {
            $this->error('BACKUP_PATH is not set in .env.');

            return self::FAILURE;
        }

        if (($connection['driver'] ?? null) !== 'mysql' && ($connection['driver'] ?? null) !== 'mariadb') {
            $this->error('Only a MySQL/MariaDB database can be backed up.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($folder);

        $file = $folder.DIRECTORY_SEPARATOR.'nuqta-shahn-'.now()->format('Y-m-d').'.sql.gz';

        if (File::exists($file) && ! $this->option('force')) {
            $this->info('Today already has a backup: '.$file);

            return self::SUCCESS;
        }

        // The password goes in the environment, not the command line, so it never shows in the process list.
        $dump = Process::timeout(300)
            ->env(['MYSQL_PWD' => (string) ($connection['password'] ?? '')])
            ->run([
                config('daftar.backup.mysqldump'),
                '--host='.$connection['host'],
                '--port='.$connection['port'],
                '--user='.$connection['username'],
                '--single-transaction',
                '--routines',
                '--default-character-set=utf8mb4',
                $connection['database'],
            ]);

        if ($dump->failed() || trim($dump->output()) === '') {
            $this->error('mysqldump failed, nothing was written: '.trim($dump->errorOutput()));

            return self::FAILURE;
        }

        // Write next to the target, then move it in place, so a half-written file never looks like a backup.
        File::put($file.'.part', gzencode($dump->output(), 9));
        File::move($file.'.part', $file);

        $this->info('Backed up to '.$file);
        $this->prune($folder);

        return self::SUCCESS;
    }

    /**
     * Keep only the newest copies.
     */
    private function prune(string $folder): void
    {
        collect(File::glob($folder.DIRECTORY_SEPARATOR.'nuqta-shahn-*.sql.gz'))
            ->sortDesc()
            ->slice(max(1, config('daftar.backup.keep')))
            ->each(function (string $old) {
                File::delete($old);
                $this->line('Removed old backup '.basename($old));
            });
    }
}
