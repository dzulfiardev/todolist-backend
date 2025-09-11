<?php

use App\Http\Controllers\Api\TodoListController;
use App\Http\Controllers\Api\TodoListsReportsController;
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

// TodoLists Reports API Routes
Route::prefix('reports/todo-lists')->group(function () {
    Route::get('/export', [TodoListsReportsController::class, 'exportExcel']);    // GET /api/reports/todo-lists/export
    Route::get('/preview', [TodoListsReportsController::class, 'previewData']);   // GET /api/reports/todo-lists/preview
});
