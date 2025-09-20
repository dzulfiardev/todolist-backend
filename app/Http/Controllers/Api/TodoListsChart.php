<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TodoLists;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TodoListsChart extends Controller
{
	/**
	 * Get chart summary data based on type parameter
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function getChart(Request $request): JsonResponse
	{
		try {
			// Validate the type parameter
			$request->validate([
				'type' => 'required|string|in:status,priority,assignee'
			]);

			$type = $request->input('type');

			switch ($type) {
				case 'status':
					return $this->_getStatusSummary();
				case 'priority':
					return $this->_getPrioritySummary();
				case 'assignee':
					return $this->_getAssigneeSummary();
				default:
					return response()->json([
						'success' => false,
						'message' => 'Invalid chart type',
						'error' => 'Supported types: status, priority, assignee'
					], 400);
			}
		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'An error occurred while generating chart data',
				'error' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * Get status summary with counts for each status
	 *
	 * @return JsonResponse
	 */
	private function _getStatusSummary(): JsonResponse
	{
		$allStatuses = [
			'pending' => TodoLists::select('status')->where('status', 'pending')->count(),
			'open' => TodoLists::select('status')->where('status', 'open')->count(),
			'in_progress' => TodoLists::select('status')->where('status', 'in_progress')->count(),
			'stuck' => TodoLists::select('status')->where('status', 'stuck')->count(),
			'completed' => TodoLists::select('status')->where('status', 'completed')->count(),
		];

		return response()->json([
			'success' => true,
			'message' => 'Status summary retrieved successfully',
			'data' => [
				'status_summary' => $allStatuses,
			]
		]);
	}

	/**
	 * Get priority summary with counts for each priority
	 *
	 * @return JsonResponse
	 */
	private function _getPrioritySummary(): JsonResponse
	{
		$allPriorities = [
			'low' => TodoLists::select('priority')->where('priority', 'low')->count(),
			'medium' => TodoLists::select('priority')->where('priority', 'medium')->count(),
			'high' => TodoLists::select('priority')->where('priority', 'high')->count(),
			'critical' => TodoLists::select('priority')->where('priority', 'critical')->count(),
			'best_effort' => TodoLists::select('priority')->where('priority', 'best_effort')->count(),
		];

		return response()->json([
			'success' => true,
			'message' => 'Priority summary retrieved successfully',
			'data' => [
				'priority_summary' => $allPriorities,
			]
		]);
	}

	/**
	 * Get assignee summary with counts for each assignee
	 *
	 * @return JsonResponse
	 */
	private function _getAssigneeSummary(): JsonResponse
	{
		$dataRaw = TodoLists::select('assigne')->whereNotNull('assigne')->where('assigne', '!=', '')->get();
		$allAssignees = [];

		foreach ($dataRaw as $data) {
			// Split assignees by comma and trim whitespace
			$assignees = array_map('trim', explode(',', $data->assigne));

			foreach ($assignees as $assignee) {
				if (!empty($assignee)) {
					// Count occurrences of each assignee
					if (isset($allAssignees[$assignee])) {
						$allAssignees[$assignee]++;
					} else {
						$allAssignees[$assignee] = 1;
					}
				}
			}
		}

		$getKeyAssignees = array_keys($allAssignees);

		$finalData = [];
		foreach ($getKeyAssignees as $assignee) {
			$finalData[] = [
				$assignee => [
					'total_todos' => TodoLists::select('task')->where('assigne', 'like', "%$assignee%")->count(),
					'total_pending_todos' => TodoLists::select('task')->where('assigne', 'like', "%$assignee%")->where('status', 'pending')->count(),
					'total_timetracked_todos' => TodoLists::select('time_tracked')->where('assigne', 'like', "%$assignee%")->sum('time_tracked'),
				]
			];
		}

		return response()->json([
			'success' => true,
			'message' => 'Assignee summary retrieved successfully',
			'data' => [
				'assignee_summary' => $finalData,
			]
		]);
	}
}
