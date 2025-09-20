<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\TodoListsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;

class TodoListsReportsController extends Controller
{
    /**
     * Export TodoLists to Excel with filtering support
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function exportExcel(Request $request)
    {
        try {
            // Validate request parameters
            $request->validate([
                'title' => 'nullable|string|max:255',
                'assigne' => 'nullable|string',
                'start' => 'nullable|date|date_format:Y-m-d',
                'end' => 'nullable|date|date_format:Y-m-d|after_or_equal:start',
                'min' => 'nullable|numeric|min:0',
                'max' => 'nullable|numeric|gte:min',
                'status' => 'nullable|string',
                'priority' => 'nullable|string'
            ]);

            // Prepare filters
            $filters = [];
            
            if ($request->filled('title')) {
                $filters['title'] = $request->input('title');
            }
            
            if ($request->filled('assigne')) {
                $filters['assigne'] = $request->input('assigne');
            }
            
            if ($request->filled('start') && $request->filled('end')) {
                $filters['start_date'] = $request->input('start');
                $filters['end_date'] = $request->input('end');
            }
            
            if ($request->filled('min') && $request->filled('max')) {
                $filters['min_time'] = $request->input('min');
                $filters['max_time'] = $request->input('max');
            }
            
            if ($request->filled('status')) {
                $filters['status'] = $request->input('status');
            }
            
            if ($request->filled('priority')) {
                $filters['priority'] = $request->input('priority');
            }

            // Generate filename with timestamp
            $filename = 'todolist_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            
            // Export to Excel
            return Excel::download(new TodoListsExport($filters), $filename);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export preview data (for testing/preview purposes)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewData(Request $request)
    {
        try {
            // Use the same validation as export
            $request->validate([
                'title' => 'nullable|string|max:255',
                'assigne' => 'nullable|string',
                'start' => 'nullable|date|date_format:Y-m-d',
                'end' => 'nullable|date|date_format:Y-m-d|after_or_equal:start',
                'min' => 'nullable|numeric|min:0',
                'max' => 'nullable|numeric|gte:min',
                'status' => 'nullable|string',
                'priority' => 'nullable|string'
            ]);

            // Prepare filters (same logic as export)
            $filters = [];
            
            if ($request->filled('title')) {
                $filters['title'] = $request->input('title');
            }
            
            if ($request->filled('assigne')) {
                $filters['assigne'] = $request->input('assigne');
            }
            
            if ($request->filled('start') && $request->filled('end')) {
                $filters['start_date'] = $request->input('start');
                $filters['end_date'] = $request->input('end');
            }
            
            if ($request->filled('min') && $request->filled('max')) {
                $filters['min_time'] = $request->input('min');
                $filters['max_time'] = $request->input('max');
            }
            
            if ($request->filled('status')) {
                $filters['status'] = $request->input('status');
            }
            
            if ($request->filled('priority')) {
                $filters['priority'] = $request->input('priority');
            }

            // Create export instance to get data
            $export = new TodoListsExport($filters);
            $data = $export->collection();
            $totalTimeTracked = $data->sum('time_tracked');

            return response()->json([
                'success' => true,
                'message' => 'Preview data retrieved successfully',
                'data' => [
                    'todos' => $data->map(function ($todo) {
                        return [
                            'title' => $todo->title,
                            'assigne' => $todo->assigne ?? '-',
                            'due_date' => $todo->due_date ? $todo->due_date->format('Y-m-d') : '-',
                            'time_tracked' => $todo->time_tracked ?? 0,
                            'status' => ucfirst($todo->status),
                            'priority' => ucfirst($todo->priority)
                        ];
                    }),
                    'summary' => [
                        'total_records' => $data->count(),
                        'total_time_tracked' => $totalTimeTracked
                    ],
                    'filters_applied' => $filters
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving preview data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
