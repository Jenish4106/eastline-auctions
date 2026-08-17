<?php

use App\Http\Controllers\CatalogImageController;
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

Route::get('/catalog/images/{id}.jpg', [CatalogImageController::class, 'generateImage']);
Route::get('/api/catalog/images/{id}.jpg', [CatalogImageController::class, 'generateImage']);
