<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'HomeController@index')->name('youtube-api.index');