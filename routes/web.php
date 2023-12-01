<?php

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
Route::get( '/download/{document}', 'App\Http\Controllers\FileUploadController@downloadFile' )->name('documents.download');
Route::post( '/download/bulk_download', 'App\Http\Controllers\FileUploadController@downloadFilesZip' )->name('documents.bulk_download');

//Route::get('/', function () {
//    return view('welcome');
//});
