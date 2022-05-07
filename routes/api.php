<?php
use Illuminate\Support\Facades\Route;
use MichaelBelgium\YoutubeAPI\Controllers\ApiController;

Route::match(['GET', 'POST'], '/convert', [ApiController::class, 'convert'])->name('youtube-api.convert');
Route::get('/search', [ApiController::class, 'search'])->name('youtube-api.search');
Route::delete('/{id}', [ApiController::class, 'delete'])->name('youtube-api.delete');