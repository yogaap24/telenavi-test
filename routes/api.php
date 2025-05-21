<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Todo\TodoController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', AuthController::class.'@login')->name('auth.login');
        Route::post('/register', AuthController::class.'@register')->name('auth.register');
    });
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::prefix('auth')->group(function () {
            Route::get('/profile', AuthController::class.'@profile')->name('auth.profile');
            Route::post('/logout', AuthController::class.'@logout')->name('auth.logout');
        });

        Route::group(['prefix' => 'users'], function () {
            Route::get('', UserController::class.'@index')->name('users.index');
            Route::post('', UserController::class.'@store')->name('users.store');
            Route::get('/{id}', UserController::class.'@show')->name('users.show');
            Route::put('/{id}', UserController::class.'@update')->name('users.update');
            Route::delete('/{id}', UserController::class.'@destroy')->name('users.destroy');
        });

        Route::group(['prefix' => 'todos'], function () {
            Route::get('', TodoController::class.'@index')->name('todos.index');
            Route::get('/export', TodoController::class.'@export')->name('todos.export');
            Route::get('/chart', TodoController::class.'@chart')->name('todos.chart');
            Route::post('', TodoController::class.'@store')->name('todos.store');
            Route::get('/{id}', TodoController::class.'@show')->name('todos.show');
            Route::put('/{id}', TodoController::class.'@update')->name('todos.update');
            Route::delete('/{id}', TodoController::class.'@destroy')->name('todos.destroy');
        });
    });
});
