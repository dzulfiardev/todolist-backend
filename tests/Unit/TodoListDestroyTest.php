<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TodoLists;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TodoListDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test single todo list deletion.
     */
    public function test_destroy_single_todo_list_success(): void
    {
        // Create a test todo list
        $todo = TodoLists::create([
            'title' => 'Test Task',
            'due_date' => '2025-09-15',
            'status' => 'pending'
        ]);

        $response = $this->deleteJson("/api/todo-lists/{$todo->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Todo list deleted successfully',
                    'deleted_id' => $todo->id
                ]);

        // Assert record is deleted from database
        $this->assertDatabaseMissing('todo_lists', ['id' => $todo->id]);
    }

    /**
     * Test bulk deletion of multiple todo lists.
     */
    public function test_destroy_multiple_todo_lists_success(): void
    {
        // Create test todo lists
        $todo1 = TodoLists::create([
            'title' => 'Test Task 1',
            'due_date' => '2025-09-15',
            'status' => 'pending'
        ]);

        $todo2 = TodoLists::create([
            'title' => 'Test Task 2',
            'due_date' => '2025-09-16',
            'status' => 'pending'
        ]);

        $todo3 = TodoLists::create([
            'title' => 'Test Task 3',
            'due_date' => '2025-09-17',
            'status' => 'pending'
        ]);

        $idsToDelete = [$todo1->id, $todo2->id];

        $response = $this->postJson('/api/todo-lists/bulk-delete', [
            'ids' => $idsToDelete
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully deleted 2 todo list(s)',
                    'deleted_count' => 2,
                    'deleted_ids' => $idsToDelete
                ]);

        // Assert deleted records are gone
        $this->assertDatabaseMissing('todo_lists', ['id' => $todo1->id]);
        $this->assertDatabaseMissing('todo_lists', ['id' => $todo2->id]);
        
        // Assert non-deleted record still exists
        $this->assertDatabaseHas('todo_lists', ['id' => $todo3->id]);
    }

    /**
     * Test bulk deletion fails with invalid IDs.
     */
    public function test_destroy_multiple_todo_lists_fails_with_invalid_ids(): void
    {
        $response = $this->postJson('/api/todo-lists/bulk-delete', [
            'ids' => [9999, 9998] // Non-existent IDs
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ])
                ->assertJsonValidationErrors(['ids.0', 'ids.1']);
    }

    /**
     * Test single deletion fails with non-existent ID.
     */
    public function test_destroy_single_todo_list_fails_with_invalid_id(): void
    {
        $response = $this->deleteJson('/api/todo-lists/9999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Todo list not found'
                ]);
    }
}
