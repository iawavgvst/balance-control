<?php

use App\Http\Controllers\Api\BalanceController;
use Illuminate\Support\Facades\Route;

// Получение баланса
Route::get('/balance/{user_id}', [BalanceController::class, 'getBalance']);
// Начисление средств (пополнение баланса)
Route::post('/deposit', [BalanceController::class, 'deposit']);
// Списание средств
Route::post('/withdraw', [BalanceController::class, 'withdraw']);
// Перевод средств между пользователями
Route::post('/transfer', [BalanceController::class, 'transfer']);
