<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChoiceController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::match(['put', 'patch'], '/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);

    Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
    Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
    Route::get('/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'show']);
    Route::match(['put', 'patch'], '/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'destroy']);

    Route::get('/quizzes/{quiz}/questions/{question}/choices', [ChoiceController::class, 'index']);
    Route::post('/quizzes/{quiz}/questions/{question}/choices', [ChoiceController::class, 'store']);
    Route::get('/quizzes/{quiz}/questions/{question}/choices/{choice}', [ChoiceController::class, 'show']);
    Route::match(['put', 'patch'], '/quizzes/{quiz}/questions/{question}/choices/{choice}', [ChoiceController::class, 'update']);
    Route::delete('/quizzes/{quiz}/questions/{question}/choices/{choice}', [ChoiceController::class, 'destroy']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
