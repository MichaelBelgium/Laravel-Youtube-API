<?php

use Illuminate\Support\Facades\Route;
use MichaelBelgium\YoutubeAPI\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('youtube-api.index');
Route::post('/', [HomeController::class, 'onPost'])->name('youtube-api.submit');
Route::get('/logs', [HomeController::class, 'logs'])->name('youtube-api.logs');