<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->folder = storage_path('framework/testing/backups');
    File::deleteDirectory($this->folder);

    config([
        'daftar.backup.path' => $this->folder,
        'daftar.backup.keep' => 3,
        'daftar.backup.mysqldump' => 'mysqldump',
        // Point the backup at a made-up MySQL connection; the tests' own database stays as it is,
        // and mysqldump is faked, so nothing real is ever reached.
        'daftar.backup.connection' => 'backup_test',
        'database.connections.backup_test' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '3306',
            'database' => 'charging_point', 'username' => 'root', 'password' => 'secret-pass',
        ],
    ]);

    $this->travelTo('2026-11-06 23:00');
});

afterEach(fn () => File::deleteDirectory($this->folder));

test('the database is dumped, compressed and saved under today\'s date', function () {
    Process::fake(['*' => Process::result(output: "CREATE TABLE customers;\n")]);

    $this->artisan('backup:database')->assertSuccessful();

    $file = $this->folder.'/nuqta-shahn-2026-11-06.sql.gz';

    expect(File::exists($file))->toBeTrue()
        ->and(gzdecode(File::get($file)))->toBe("CREATE TABLE customers;\n")
        ->and(File::exists($file.'.part'))->toBeFalse();

    // The password reaches mysqldump through the environment, never the command line.
    Process::assertRan(fn (PendingProcess $process) => $process->environment['MYSQL_PWD'] === 'secret-pass'
        && ! in_array('secret-pass', $process->command, true)
        && in_array('charging_point', $process->command, true));
});

test('a day already backed up is skipped, unless forced', function () {
    Process::fake(['*' => Process::result(output: 'first')]);
    $this->artisan('backup:database');

    Process::fake(['*' => Process::result(output: 'second')]);
    $this->artisan('backup:database')->assertSuccessful();

    $file = $this->folder.'/nuqta-shahn-2026-11-06.sql.gz';
    expect(trim(gzdecode(File::get($file))))->toBe('first');

    $this->artisan('backup:database --force')->assertSuccessful();
    expect(trim(gzdecode(File::get($file))))->toBe('second');
});

test('a failed dump writes nothing and reports the failure', function () {
    Process::fake(['*' => Process::result(output: '', errorOutput: 'Access denied', exitCode: 2)]);

    $this->artisan('backup:database')->assertFailed();

    expect(File::glob($this->folder.'/*'))->toBeEmpty();
});

test('only the newest copies are kept', function () {
    File::ensureDirectoryExists($this->folder);
    foreach (['2026-11-01', '2026-11-02', '2026-11-03', '2026-11-04'] as $day) {
        File::put($this->folder."/nuqta-shahn-{$day}.sql.gz", 'old');
    }
    Process::fake(['*' => Process::result(output: 'today')]);

    $this->artisan('backup:database')->assertSuccessful();

    expect(collect(File::glob($this->folder.'/*'))->map(fn ($path) => basename($path))->values()->all())
        ->toBe(['nuqta-shahn-2026-11-03.sql.gz', 'nuqta-shahn-2026-11-04.sql.gz', 'nuqta-shahn-2026-11-06.sql.gz']);
});

test('without a backup folder it refuses', function () {
    config(['daftar.backup.path' => null]);
    Process::fake();

    $this->artisan('backup:database')->assertFailed();

    Process::assertNothingRan();
});
