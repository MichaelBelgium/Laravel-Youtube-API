<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Routing prefix
    |--------------------------------------------------------------------------
    |
    | This value is the section of all the api routes after 'api/' 
    | Example: api/ytconverter/convert
    |
    */
    
    'route_prefix' => 'ytconverter',

    /*
    |--------------------------------------------------------------------------
    | Maximum download length
    |--------------------------------------------------------------------------
    |
    | Specify in seconds what the maximum duration length can be for converting. Set to 0 to disable
    |
    */

    'download_max_length' => 0,

    /*
    |--------------------------------------------------------------------------
    | Maximum search results
    |--------------------------------------------------------------------------
    |
    | Specify the maximum amount of results the search route returns
    |
    */

    'search_max_results' => 10,

    /*
    |--------------------------------------------------------------------------
    | FFMPEG bin path
    |--------------------------------------------------------------------------
    |
    | The location of the ffmpeg executable in case when manually build in stead of yum install or apt-get install
    |
    */

    'ffmpeg_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Enable authentication
    |--------------------------------------------------------------------------
    |
    | If true, middleware 'auth:api' will be used for api routes
    |
    */

    'enable_auth' => false,
];