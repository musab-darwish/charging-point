<?php

return [

    /*
    | The owner account, created by DatabaseSeeder. Other users are added by
    | the owner from the users page. These values live in .env so the real
    | name and password never reach the public repository.
    */

    'owner' => [
        'name' => env('SEED_OWNER_NAME'),
        'username' => env('SEED_OWNER_USERNAME'),
        'password' => env('SEED_OWNER_PASSWORD'),
    ],

    /*
    | The daily database backup (php artisan backup:database). The folder is
    | meant to be one Google Drive for desktop syncs, so every copy also
    | leaves the laptop. mysqldump comes with XAMPP (C:\xampp\mysql\bin).
    */

    'backup' => [
        'path' => env('BACKUP_PATH'),
        'connection' => env('BACKUP_CONNECTION'), // empty: the app's own database connection
        'keep' => (int) env('BACKUP_KEEP', 30),
        'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),
    ],

];
