<?php
use Illuminate\Support\Facades\Route;
use MichaelBelgium\YoutubeAPI\Controllers\ApiController;

Route::post('/convert', [ApiController::class, 'convert'])->name('youtube-api.convert');
Route::delete('/{id}', [ApiController::class, 'delete'])->name('youtube-api.delete');
Route::get('/search/{q}', [ApiController::class, 'search'])->name('youtube-api.search');