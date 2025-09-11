<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TodoLists;
use App\Helpers\TodoListHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TodoListController extends Controller
{
	private $orderBy = 'id';
	private $orderDirection = 'desc';

	public function index(Request $request): JsonResponse
	{
		try {
			// Build the query
			$query = TodoLists::query();

			// Apply search filter if provided
			if ($request->has('search') && !empty($request->search)) {
				$searchTerm = $request->search;
				$query->where(function ($q) use ($searchTerm) {
					$q->where('title', 'LIKE', '%' . $searchTerm . '%');
				});
			}

			if ($request->has('sort_by') && in_array($request->sort_by, ['id', 'title', 'due_date', 'status', 'priority', 'type', 'estimated_sp', 'actual_sp'])) {
				$this->orderBy = $request->sort_by;
			}

			if ($request->has('order_direction') && in_array(strtolower($request->order_direction), ['asc', 'desc'])) {
				$this->orderDirection = strtolower($request->order_direction);
			}	

			// Get the results ordered by ID descending
			// $todoLists = $query->orderBy($this->orderBy, $this->orderDirection)->get();
			$todoLists = $query->orderBy($this->orderBy, $this->orderDirection)->get();

			$formattedData = $todoLists->map(function ($todo) {
				return [
					'id' => $todo->id,
					'task' => $todo->title,
					'developer' => TodoListHelper::convertStringToArray($todo->assigne),
					'date' => TodoListHelper::formatDate($todo->due_date),
					'time_tracked' => $todo->time_tracked,
					'status' => TodoListHelper::formatEnumValue($todo->status),
					'status_raw' => $todo->status,
					'priority' => TodoListHelper::formatEnumValue($todo->priority),
					'type' => TodoListHelper::formatEnumValue($todo->type),
					'estimated_sp' => $todo->estimated_sp,
					'actual_sp' => $todo->actual_sp,
				];
			});

			return response()->json([
				'success' => true,
				'message' => 'Todo lists retrieved successfully',
				'data' => $formattedData,
				'search' => $request->input('search', null), // Include search term in response for reference
				'total_count' => $formattedData->count()
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to retrieve todo lists',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function store(Request $request): JsonResponse
	{
		try {
			$validatedData = $request->validate([
				'task' => 'nullable|string|max:255',
				'developer' => 'nullable|string|max:255',
				'due_date' => 'nullable|date|after_or_equal:today',
				'time_tracked' => 'nullable|integer|min:0',
				'status' => 'nullable|in:pending,open,in_progress,completed,stuck',
				'priority' => 'nullable|in:low,medium,high,critical,best_effort',
				'type' => 'nullable|in:feature_enhancements,other,bug',
				'estimated_sp' => 'nullable|integer|min:0',
				'actual_sp' => 'nullable|integer|min:0'
			]);

			if (!isset($validatedData['task']) || empty($validatedData['task'])) {
				$validatedData['task'] = 'New Task';
			}

			if (!isset($validatedData['due_date']) || empty($validatedData['due_date'])) {
				$validatedData['due_date'] = now()->format('Y-m-d');
			}

			if (!isset($validatedData['status']) || empty($validatedData['status'])) {
				$validatedData['status'] = 'pending';
			}

			if (!isset($validatedData['time_tracked']) || empty($validatedData['time_tracked'])) {
				$validatedData['time_tracked'] = 0;
			}

			$mapingPayloads = [
				'title' => $validatedData['task'] ?? '',
				'assigne' => $validatedData['developer'] ?? '',
				'due_date' => $validatedData['due_date'] ?? '',
				'time_tracked' => $validatedData['time_tracked'] ?? '',
				'status' => $validatedData['status'] ?? null,
				'priority' => $validatedData['priority'] ?? null,
				'type' => $validatedData['type'] ?? null,
				'estimated_sp' => $validatedData['estimated_sp'] ?? null,
				'actual_sp' => $validatedData['actual_sp'] ?? null
			];

			$todoList = TodoLists::create($mapingPayloads);

			return response()->json([
				'success' => true,
				'message' => 'Todo list created successfully',
				'data' => $todoList
			], 201);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to create todo list',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function show(string $id): JsonResponse
	{
		try {
			$todoList = TodoLists::findOrFail($id);

			return response()->json([
				'success' => true,
				'message' => 'Todo list retrieved successfully',
				'data' => $todoList
			], 200);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Todo list not found'
			], 404);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to retrieve todo list',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function update(Request $request, string $id): JsonResponse
	{
		try {
			$todoList = TodoLists::findOrFail($id);

			$validatedData = $request->validate([
				'task' => 'nullable|string|max:255',
				'developer' => 'nullable',
				'date' => 'sometimes|required|date|after_or_equal:today',
				'status' => 'nullable|in:pending,open,in_progress,completed,stuck',
				'priority' => 'nullable|in:low,medium,high,critical,best_effort',
				'type' => 'nullable|in:feature_enhancements,other,bug',
				'estimated_sp' => 'nullable|integer|min:0',
				'actual_sp' => 'nullable|integer|min:0'
			]);

			// Convert 'developer' to string if it's an array
			if (isset($validatedData['developer']) && is_array($validatedData['developer'])) {
				$validatedData['developer'] = TodoListHelper::convertArrayToString($validatedData['developer']);
			}

			$mapingPayloads = [
				'title' => $validatedData['task'] ?? $todoList->title,
				'assigne' => $validatedData['developer'] ?? $todoList->assigne,
				'due_date' => $validatedData['date'] ?? $todoList->due_date,
				'status' => $validatedData['status'] ?? $todoList->status,
				'priority' => $validatedData['priority'] ?? $todoList->priority,
				'type' => $validatedData['type'] ?? $todoList->type,
				'estimated_sp' => $validatedData['estimated_sp'] ?? $todoList->estimated_sp,
				'actual_sp' => $validatedData['actual_sp'] ?? $todoList->actual_sp
			];

			$todoList->update($mapingPayloads);

			return response()->json([
				'success' => true,
				'message' => 'Todo list updated successfully',
				'data' => $todoList->fresh()
			], 200);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Todo list not found'
			], 404);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update todo list',
				'error' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * Remove the specified resource(s) from storage.
	 * Accepts either a single ID from URL parameter or multiple IDs from request body.
	 */
	public function destroy(Request $request, string $id = null): JsonResponse
	{
		try {
			// Check if IDs are provided in request body for bulk deletion
			if ($request->has('ids')) {
				$validatedData = $request->validate([
					'ids' => 'required|array|min:1',
					'ids.*' => 'required|integer|exists:todo_lists,id'
				]);

				$ids = $validatedData['ids'];
				$deletedCount = TodoLists::whereIn('id', $ids)->delete();

				if ($deletedCount === 0) {
					return response()->json([
						'success' => false,
						'message' => 'No todo lists found to delete'
					], 404);
				}

				return response()->json([
					'success' => true,
					'message' => "Successfully deleted {$deletedCount} todo list(s)",
					'deleted_count' => $deletedCount,
					'deleted_ids' => $ids
				], 200);
			}
			// Single deletion using URL parameter
			else if ($id) {
				$todoList = TodoLists::findOrFail($id);
				$todoList->delete();

				return response()->json([
					'success' => true,
					'message' => 'Todo list deleted successfully',
					'deleted_id' => (int)$id
				], 200);
			}
			// No ID provided
			else {
				return response()->json([
					'success' => false,
					'message' => 'No ID or IDs provided for deletion'
				], 400);
			}
		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Todo list not found'
			], 404);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to delete todo list(s)',
				'error' => $e->getMessage()
			], 500);
		}
	}
}
