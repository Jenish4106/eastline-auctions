<?php

use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json(['message' => 'Welcome to the API']);
});

Route::get('/meta-catalog-feed', [FeedController::class, 'metaCatalogFeed']);
Route::get('/meta-catalog-feed.csv', [FeedController::class, 'metaCatalogFeed']);
Route::get('/api/meta-catalog-feed', [FeedController::class, 'metaCatalogFeed']);
Route::get('/api/meta-catalog-feed.csv', [FeedController::class, 'metaCatalogFeed']);

Route::get('/catalog-image/{id}', [FeedController::class, 'catalogImage']);
Route::get('/catalog-image/{id}.png', [FeedController::class, 'catalogImage']);
Route::get('/catalog-image/{id}.jpg', [FeedController::class, 'catalogImage']);
Route::get('/api/catalog-image/{id}', [FeedController::class, 'catalogImage']);
Route::get('/api/catalog-image/{id}.png', [FeedController::class, 'catalogImage']);
Route::get('/api/catalog-image/{id}.jpg', [FeedController::class, 'catalogImage']);
