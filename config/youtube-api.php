<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Youtube API driver
    |--------------------------------------------------------------------------
    |
    | This value determines which driver to use for the youtube api
    |   Accepts:
    |       - local (default)
    |       - cobalt (requires cobalt server)
     */
    'driver' => env('YOUTUBE_API_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Cobalt server configuration
    |--------------------------------------------------------------------------
    |
    | This value is the configuration for the cobalt driver
    |   url: the url of the cobalt server
    |   hls: whether to use HLS or not
    |   auth: "Authorization" header for the cobalt server
    |
     */
    'cobalt' => [
        'url' => null,
        'hls' => false,
        'auth' => env('YOUTUBE_API_COBALT_AUTH'),
    ],

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
    | Set download length limit
    |--------------------------------------------------------------------------
    |
    | If not null, sets a limit on the video length that users can convert
    |   Accepts:
    |       - null
    |       - an anonymous function with Illuminate\Http\Request parameter and returning an integer, the max video length in seconds
    |
    |   Examples:
    |       function (Illuminate\Http\Request $request) 
    |       {
    |           $plan = $request->user()->getCurrentPlan();
    |
    |            return $plan->download_limit;
    |       }
    |
    |       function (Illuminate\Http\Request $request) 
    |       {
    |           return 300;
    |       }
    */

    'videolength_limiter' => null,

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
    | Set authentication
    |--------------------------------------------------------------------------
    |
    | If not null, it'll attempt to use the defined authentication guard for api routes
    |
    */

    'auth' => null,

    /*
    |--------------------------------------------------------------------------
    | Set ratelimiter
    |--------------------------------------------------------------------------
    |
    | If not null, sets a ratelimiter on the api group routes
    | Accepts:
    |       - null
    |       - an anonymous function with Illuminate\Http\Request parameter and returning an instance of Illuminate\Cache\RateLimiting\Limit
    |
    | More info: https://laravel.com/docs/8.x/routing#rate-limiting
    |
    */
    'ratelimiter' => null,

    /*
    |--------------------------------------------------------------------------
    | Enable logging
    |--------------------------------------------------------------------------
    |
    | Save an entry every time a video gets converted into logs table.
    |
    | Note: this also enables the page /logs with an overview of all songs that have been converted
    |
    */
    'enable_logging' => false,

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | If not null, sets a proxy for yt-dlp or youtube-dl.
    | Accepts:
    |       - null
    |       - a string representing the proxy URL
    |
    */
    'proxy' => env('YOUTUBE_API_PROXY'),
];