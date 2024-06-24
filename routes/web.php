<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
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

// index of IndexController
Route::get('/home', [IndexController::class, 'index'])->name('home');
Route::post('/upload', [IndexController::class, 'uploadFile'])->name('upload');
Route::post('/assign-tokens', [IndexController::class, 'assignTokens'])->name('assignTokens');
Route::get('/download-output', [IndexController::class, 'downloadOutput'])->name('downloadOutput');
Route::post('/reset', [IndexController::class, 'initOutputFile'])->name('reset');
Route::get('/', function () {
    return redirect()->route('home');
});
