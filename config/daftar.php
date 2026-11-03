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

];
