<?php

use Illuminate\Support\Facades\Route;
use Matakltm\LaravelModelGraph\Http\Controllers\GraphController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your package.
|
*/

Route::group(['middleware' => config('model-graph.middleware', ['web'])], function () {
    Route::get('/graph', [GraphController::class, 'index'])->name('model-graph.index');
});
