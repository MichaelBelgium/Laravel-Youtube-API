<?php
use Illuminate\Support\Facades\Route;

Route::post('/convert', 'ApiController@convert')->name('youtube-api.convert');
Route::delete('/{id}', 'ApiController@delete')->name('youtube-api.delete');
Route::get('/search/{q}', 'ApiController@search')->name('youtube-api.search');