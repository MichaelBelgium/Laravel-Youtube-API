<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'ytconverter',
    'namespace' => 'MichaelBelgium\YoutubeAPI\Controllers'
], function() {
    Route::post('/convert', 'ApiController@convert');
    Route::delete('/{id}', 'ApiController@delete');
    Route::get('/search/{q}', 'ApiController@search');
});
