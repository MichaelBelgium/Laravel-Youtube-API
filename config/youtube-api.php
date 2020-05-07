<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Route prefix
     |--------------------------------------------------------------------------
     |
     |
     |
     |
    */
    
    'route_prefix' => 'ytconverter',

    'download' => [
        'max_length' => 0,
        'path' => storage_path('app/public') . '/'
    ],

    'search_max_results' => 10
];