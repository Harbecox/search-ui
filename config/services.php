<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Search Service
    |--------------------------------------------------------------------------
    */
    'search_api' => [
        'url'           => env('SEARCH_API_URL', 'http://100.81.37.54:8000'),
        'token'         => env('SEARCH_API_TOKEN'),
        'default_limit' => env('SEARCH_DEFAULT_LIMIT', 10),
    ],

];
