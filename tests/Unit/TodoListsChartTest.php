<?php

namespace Tests\Unit;

use App\Models\TodoLists;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoListsChartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test chart API with status type returns correct counts
     */
    public function test_chart_status_summary_returns_correct_counts()
    {
        // Create test data with known status distribution
        TodoLists::create([
            'title' => 'Test Task 1',
            'status' => 'pending',
            'priority' => 'medium',
            'type' => 'bug',
            'due_date' => '2025-09-15'
        ]);

        TodoLists::create([
            'title' => 'Test Task 2',
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-16'
        ]);

        TodoLists::create([
            'title' => 'Test Task 3',
            'status' => 'in_progress',
            'priority' => 'low',
            'type' => 'bug',
            'due_date' => '2025-09-17'
        ]);

        TodoLists::create([
            'title' => 'Test Task 4',
            'status' => 'completed',
            'priority' => 'medium',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-18'
        ]);

        TodoLists::create([
            'title' => 'Test Task 5',
            'status' => 'stuck',
            'priority' => 'high',
            'type' => 'other',
            'due_date' => '2025-09-19'
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=status');

        // Assert response structure and status
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Status summary retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'status_summary' => [
                            'pending',
                            'open',
                            'in_progress',
                            'stuck',
                            'completed'
                        ]
                    ]
                ]);

        // Assert specific counts
        $data = $response->json('data.status_summary');
        $this->assertEquals(2, $data['pending']);
        $this->assertEquals(0, $data['open']); // No open tasks created
        $this->assertEquals(1, $data['in_progress']);
        $this->assertEquals(1, $data['stuck']);
        $this->assertEquals(1, $data['completed']);
    }

    /**
     * Test chart API requires type parameter
     */
    public function test_chart_requires_type_parameter()
    {
        $response = $this->getJson('/api/chart');

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'type' => ['The type field is required.']
                    ]
                ]);
    }

    /**
     * Test chart API validates type parameter
     */
    public function test_chart_validates_type_parameter()
    {
        $response = $this->getJson('/api/chart?type=invalid');

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'type' => ['The selected type is invalid.']
                    ]
                ]);
    }

    /**
     * Test chart API with priority type returns correct counts
     */
    public function test_chart_priority_summary_returns_correct_counts()
    {
        // Create test data with known priority distribution
        TodoLists::create([
            'title' => 'Low Priority Task',
            'status' => 'pending',
            'priority' => 'low',
            'type' => 'bug',
            'due_date' => '2025-09-15'
        ]);

        TodoLists::create([
            'title' => 'Medium Priority Task',
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-16'
        ]);

        TodoLists::create([
            'title' => 'High Priority Task',
            'status' => 'completed',
            'priority' => 'high',
            'type' => 'bug',
            'due_date' => '2025-09-17'
        ]);

        TodoLists::create([
            'title' => 'Critical Priority Task',
            'status' => 'stuck',
            'priority' => 'critical',
            'type' => 'other',
            'due_date' => '2025-09-18'
        ]);

        TodoLists::create([
            'title' => 'Best Effort Task',
            'status' => 'open',
            'priority' => 'best_effort',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-19'
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=priority');

        // Assert response structure and status
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Priority summary retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'priority_summary' => [
                            'low',
                            'medium',
                            'high',
                            'critical',
                            'best_effort'
                        ]
                    ]
                ]);

        // Assert specific counts
        $data = $response->json('data.priority_summary');
        $this->assertEquals(1, $data['low']);
        $this->assertEquals(1, $data['medium']);
        $this->assertEquals(1, $data['high']);
        $this->assertEquals(1, $data['critical']);
        $this->assertEquals(1, $data['best_effort']);
    }

    /**
     * Test chart API with empty database returns zeros
     */
    public function test_chart_status_summary_with_empty_database()
    {
        // Ensure database is empty
        $this->assertEquals(0, TodoLists::count());

        $response = $this->getJson('/api/chart?type=status');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'status_summary' => [
                            'pending' => 0,
                            'open' => 0,
                            'in_progress' => 0,
                            'stuck' => 0,
                            'completed' => 0
                        ]
                    ]
                ]);
    }

    /**
     * Test chart API with assignee type returns correct data structure
     */
    public function test_chart_assignee_summary_returns_correct_structure()
    {
        // Create test data with known assignee distribution
        TodoLists::create([
            'title' => 'Task for John',
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'bug',
            'due_date' => '2025-09-15',
            'assigne' => 'John Doe',
            'time_tracked' => 5
        ]);

        TodoLists::create([
            'title' => 'Task for Jane',
            'status' => 'in_progress',
            'priority' => 'medium',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-16',
            'assigne' => 'Jane Smith',
            'time_tracked' => 3
        ]);

        TodoLists::create([
            'title' => 'Task for multiple',
            'status' => 'completed',
            'priority' => 'low',
            'type' => 'other',
            'due_date' => '2025-09-17',
            'assigne' => 'John Doe, Jane Smith',
            'time_tracked' => 8
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=assignee');

        // Assert response structure and status
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Assignee summary retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'assignee_summary' => [
                            '*' => [
                                '*' => [
                                    'total_todos',
                                    'total_pending_todos',
                                    'total_timetracked_todos'
                                ]
                            ]
                        ]
                    ]
                ]);

        // Assert that we have assignee data
        $data = $response->json('data.assignee_summary');
        $this->assertNotEmpty($data);
        $this->assertIsArray($data);
    }

    /**
     * Test chart API with assignee type handles single assignee correctly
     */
    public function test_chart_assignee_summary_single_assignee()
    {
        // Create test data with single assignee
        TodoLists::create([
            'title' => 'Task 1 for Alice',
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'bug',
            'due_date' => '2025-09-15',
            'assigne' => 'Alice Johnson',
            'time_tracked' => 4
        ]);

        TodoLists::create([
            'title' => 'Task 2 for Alice',
            'status' => 'completed',
            'priority' => 'medium',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-16',
            'assigne' => 'Alice Johnson',
            'time_tracked' => 6
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=assignee');

        $response->assertStatus(200);

        $data = $response->json('data.assignee_summary');
        $this->assertCount(1, $data); // Should have one assignee

        // Find Alice's data
        $aliceData = null;
        foreach ($data as $assigneeData) {
            if (isset($assigneeData['Alice Johnson'])) {
                $aliceData = $assigneeData['Alice Johnson'];
                break;
            }
        }

        $this->assertNotNull($aliceData);
        $this->assertEquals(2, $aliceData['total_todos']);
        $this->assertEquals(1, $aliceData['total_pending_todos']);
        $this->assertEquals(10, $aliceData['total_timetracked_todos']);
    }

    /**
     * Test chart API with assignee type handles multiple assignees correctly
     */
    public function test_chart_assignee_summary_multiple_assignees()
    {
        // Create test data with multiple assignees in one task
        TodoLists::create([
            'title' => 'Collaborative Task',
            'status' => 'in_progress',
            'priority' => 'high',
            'type' => 'bug',
            'due_date' => '2025-09-15',
            'assigne' => 'Bob Wilson, Carol Davis, David Brown',
            'time_tracked' => 12
        ]);

        TodoLists::create([
            'title' => 'Bob Solo Task',
            'status' => 'pending',
            'priority' => 'medium',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-16',
            'assigne' => 'Bob Wilson',
            'time_tracked' => 5
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=assignee');

        $response->assertStatus(200);

        $data = $response->json('data.assignee_summary');
        $this->assertCount(3, $data); // Should have three unique assignees

        // Check that all assignees are present
        $assigneeNames = [];
        foreach ($data as $assigneeData) {
            $assigneeNames = array_merge($assigneeNames, array_keys($assigneeData));
        }

        $this->assertContains('Bob Wilson', $assigneeNames);
        $this->assertContains('Carol Davis', $assigneeNames);
        $this->assertContains('David Brown', $assigneeNames);
    }

    /**
     * Test chart API with assignee type handles empty and null assignees
     */
    public function test_chart_assignee_summary_handles_empty_assignees()
    {
        // Create test data with empty and null assignees
        TodoLists::create([
            'title' => 'Task with no assignee',
            'status' => 'pending',
            'priority' => 'low',
            'type' => 'bug',
            'due_date' => '2025-09-15',
            'assigne' => null,
            'time_tracked' => 2
        ]);

        TodoLists::create([
            'title' => 'Task with empty assignee',
            'status' => 'open',
            'priority' => 'medium',
            'type' => 'other',
            'due_date' => '2025-09-16',
            'assigne' => '',
            'time_tracked' => 3
        ]);

        TodoLists::create([
            'title' => 'Task with valid assignee',
            'status' => 'completed',
            'priority' => 'high',
            'type' => 'feature_enhancements',
            'due_date' => '2025-09-17',
            'assigne' => 'Valid User',
            'time_tracked' => 7
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=assignee');

        $response->assertStatus(200);

        $data = $response->json('data.assignee_summary');
        $this->assertCount(1, $data); // Should only have one valid assignee

        // Check that only valid assignee is included
        $assigneeNames = [];
        foreach ($data as $assigneeData) {
            $assigneeNames = array_merge($assigneeNames, array_keys($assigneeData));
        }

        $this->assertContains('Valid User', $assigneeNames);
        $this->assertNotContains('', $assigneeNames);
        $this->assertNotContains(null, $assigneeNames);
    }

    /**
     * Test chart API with assignee type returns empty array when no valid assignees
     */
    public function test_chart_assignee_summary_empty_when_no_valid_assignees()
    {
        // Create test data with only empty/null assignees
        TodoLists::create([
            'title' => 'Task 1',
            'status' => 'pending',
            'priority' => 'low',
            'type' => 'bug',
            'due_date' => '2025-09-15',
            'assigne' => null
        ]);

        TodoLists::create([
            'title' => 'Task 2',
            'status' => 'open',
            'priority' => 'medium',
            'type' => 'other',
            'due_date' => '2025-09-16',
            'assigne' => ''
        ]);

        // Call the API
        $response = $this->getJson('/api/chart?type=assignee');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Assignee summary retrieved successfully',
                    'data' => [
                        'assignee_summary' => []
                    ]
                ]);
    }
}
