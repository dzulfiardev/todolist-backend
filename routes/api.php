<?php

use App\Http\Controllers\Api\TodoListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Todo Lists API Routes
// Route::apiResource('todo-lists', TodoListController::class);

Route::prefix('todo-lists')->group(function () {
    Route::get('/', [TodoListController::class, 'index']);           // GET /api/todo-lists
    Route::post('/', [TodoListController::class, 'store']);          // POST /api/todo-lists
    Route::post('/bulk-delete', [TodoListController::class, 'destroy']); // POST /api/todo-lists/bulk-delete (for multiple IDs)
    Route::get('/{id}', [TodoListController::class, 'show']);        // GET /api/todo-lists/{id}
    Route::put('/{id}', [TodoListController::class, 'update']);      // PUT /api/todo-lists/{id}
    Route::patch('/{id}', [TodoListController::class, 'update']);    // PATCH /api/todo-lists/{id}
    Route::delete('/{id}', [TodoListController::class, 'destroy']);  // DELETE /api/todo-lists/{id} (single)
});
