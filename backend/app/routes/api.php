<?php

use App\Http\Controllers\AssociationController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/user/create', [UserController::class, 'create']);
Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/forgot-password', [UserController::class, 'forgot_password']);
Route::post('/user/reset-password', [UserController::class, 'reset_password']);
Route::post('/user/accept-invitation', [UserController::class, 'accept_invitation']);

Route::post('/association/create', [AssociationController::class, 'create']);
Route::post('/unit/create', [UnitController::class, 'create']);
Route::post('/unit/assign-owner', [UnitController::class, 'assign_owner']);


Route::get('/user/test', [UserController::class, 'test']);
