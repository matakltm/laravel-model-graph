<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your package.
|
*/

use Matakltm\LaravelModelGraph\Http\Controllers\GraphController;

Route::group(['middleware' => config('model-graph.middleware', ['web'])], function () {
    Route::get('/graph', [GraphController::class, 'index'])->name('model-graph.index');
    Route::get('/graph/assets/{file}', [GraphController::class, 'asset'])
        ->where('file', '.*')
        ->name('model-graph.assets');
});
