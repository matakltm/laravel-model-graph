<?php

use Illuminate\Support\Facades\Route;
use Matakltm\LaravelModelGraph\Http\Controllers\GraphApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your package.
|
*/

Route::group([
    'prefix' => 'api/v1',
    'middleware' => config('model-graph.api_middleware', ['web']),
], function () {
    Route::get('/graph/data', [GraphApiController::class, 'data'])->name('model-graph.api.data');
});
