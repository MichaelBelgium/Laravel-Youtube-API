<?php

return [
    'download' => [
        'max_length' => env('DOWNLOAD_MAX_LENGTH', 0),
        'path' => storage_path('app/public') . '/'
    ],
    'search_max_results' => env('SEARCH_MAX_RESULTS', 10)
];