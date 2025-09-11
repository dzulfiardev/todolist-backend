<?php

namespace Tests\Unit;

use App\Exports\TodoListsExport;
use App\Models\TodoLists;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;

class TodoListsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_creates_excel_file_successfully()
    {
        // Create test data
        TodoLists::create([
            'title' => 'Test Task 1',
            'assigne' => 'John Doe',
            'due_date' => '2025-12-31',
            'time_tracked' => 5,
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements'
        ]);

        TodoLists::create([
            'title' => 'Test Task 2',
            'assigne' => 'Jane Smith',
            'due_date' => '2025-11-15',
            'time_tracked' => 3,
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'bug'
        ]);

        // Test export without filters
        $export = new TodoListsExport();
        $collection = $export->collection();

        $this->assertCount(2, $collection);
        // The collection is ordered by due_date ascending, so Test Task 2 (2025-11-15) comes first
        $this->assertEquals('Test Task 2', $collection->first()->title);
        $this->assertEquals('Test Task 1', $collection->last()->title);
    }

    public function test_export_with_title_filter()
    {
        // Create test data
        TodoLists::create([
            'title' => 'Important Task',
            'assigne' => 'John Doe',
            'due_date' => '2025-12-31',
            'time_tracked' => 5,
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements'
        ]);

        TodoLists::create([
            'title' => 'Regular Task',
            'assigne' => 'Jane Smith',
            'due_date' => '2025-11-15',
            'time_tracked' => 3,
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'bug'
        ]);

        // Test export with title filter
        $filters = ['title' => 'Important'];
        $export = new TodoListsExport($filters);
        $collection = $export->collection();

        $this->assertCount(1, $collection);
        $this->assertEquals('Important Task', $collection->first()->title);
    }

    public function test_export_with_multiple_assignees_filter()
    {
        // Create test data
        TodoLists::create([
            'title' => 'Task 1',
            'assigne' => 'John',
            'due_date' => '2025-12-31',
            'time_tracked' => 5,
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements'
        ]);

        TodoLists::create([
            'title' => 'Task 2',
            'assigne' => 'Jane',
            'due_date' => '2025-11-15',
            'time_tracked' => 3,
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'bug'
        ]);

        TodoLists::create([
            'title' => 'Task 3',
            'assigne' => 'Bob',
            'due_date' => '2025-10-15',
            'time_tracked' => 2,
            'status' => 'completed',
            'priority' => 'low',
            'type' => 'other'
        ]);

        // Test export with multiple assignees filter
        $filters = ['assigne' => 'John,Jane'];
        $export = new TodoListsExport($filters);
        $collection = $export->collection();

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->contains('assigne', 'John'));
        $this->assertTrue($collection->contains('assigne', 'Jane'));
        $this->assertFalse($collection->contains('assigne', 'Bob'));
    }

    public function test_export_with_date_range_filter()
    {
        // Create test data
        TodoLists::create([
            'title' => 'Task 1',
            'assigne' => 'John',
            'due_date' => '2025-09-15',
            'time_tracked' => 5,
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements'
        ]);

        TodoLists::create([
            'title' => 'Task 2',
            'assigne' => 'Jane',
            'due_date' => '2025-12-15',
            'time_tracked' => 3,
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'bug'
        ]);

        // Test export with date range filter
        $filters = [
            'start_date' => '2025-09-01',
            'end_date' => '2025-10-31'
        ];
        $export = new TodoListsExport($filters);
        $collection = $export->collection();

        $this->assertCount(1, $collection);
        $this->assertEquals('Task 1', $collection->first()->title);
    }

    public function test_export_column_mapping()
    {
        // Create test data
        $todo = TodoLists::create([
            'title' => 'Test Task',
            'assigne' => 'John Doe',
            'due_date' => '2025-12-31',
            'time_tracked' => 5,
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements'
        ]);

        $export = new TodoListsExport();
        $mapped = $export->map($todo);

        $this->assertEquals('Test Task', $mapped[0]);
        $this->assertEquals('John Doe', $mapped[1]);
        $this->assertEquals('2025-12-31', $mapped[2]);
        $this->assertEquals(5, $mapped[3]);
        $this->assertEquals('Pending', $mapped[4]);
        $this->assertEquals('High', $mapped[5]);
    }

    public function test_export_headings()
    {
        $export = new TodoListsExport();
        $headings = $export->headings();

        $expected = [
            'Title',
            'Assignee',
            'Due Date',
            'Time Tracked (Hours)',
            'Status',
            'Priority'
        ];

        $this->assertEquals($expected, $headings);
    }
}
