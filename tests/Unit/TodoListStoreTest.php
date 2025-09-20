<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TodoLists;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TodoListStoreTest extends TestCase
{
	use RefreshDatabase, WithFaker;

	/**
	 * Test successful todo list creation with completely empty data structure.
	 * Should apply all default values: title='New Task', status='pending', due_date=today.
	 */
	public function test_store_todo_list_success_with_empty_data(): void
	{
		// Send completely empty data
		$todoData = [];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(201)
			->assertJson([
				'success' => true,
				'message' => 'Todo list created successfully'
			])
			->assertJsonStructure([
				'success',
				'message',
				'data' => [
					'id',
					'title',
					'due_date',
					'status',
					'created_at',
					'updated_at'
				]
			]);

		// Check response data contains expected default values
		$responseData = $response->json('data');
		$this->assertEquals('New Task', $responseData['title']);
		$this->assertEquals('pending', $responseData['status']);

		// For due_date, check if it's today's date (may come in different format from API)
		$expectedDate = now()->format('Y-m-d');
		$actualDate = \Carbon\Carbon::parse($responseData['due_date'])->format('Y-m-d');
		$this->assertEquals($expectedDate, $actualDate);

		// Assert defaults are stored in database
		$this->assertDatabaseHas('todo_lists', [
			'title' => 'New Task',
			'status' => 'pending',
			'due_date' => now()->format('Y-m-d')
		]);

		// Verify the created record has all expected default values
		$createdTodo = \App\Models\TodoLists::find($responseData['id']);
		$this->assertNotNull($createdTodo);
		$this->assertEquals('New Task', $createdTodo->title);
		$this->assertEquals('pending', $createdTodo->status);
		$this->assertEquals(now()->format('Y-m-d'), $createdTodo->due_date->format('Y-m-d'));
		$this->assertEquals('', $createdTodo->assigne); // Empty string instead of null
		$this->assertEquals(0, $createdTodo->time_tracked);
		$this->assertNull($createdTodo->priority);
		$this->assertNull($createdTodo->type);
		$this->assertNull($createdTodo->estimated_sp);
		$this->assertNull($createdTodo->actual_sp);
	}

	/**
	 * Test successful todo list creation with all data provided.
	 */
	public function test_store_todo_list_success_with_full_data(): void
	{
		$todoData = [
			'task' => 'Complete project documentation',
			'developer' => 'John Doe',
			'due_date' => '2025-09-15',
			'time_tracked' => 120,
			'status' => 'in_progress',
			'priority' => 'high',
			'type' => 'feature_enhancements',
			'estimated_sp' => 5,
			'actual_sp' => 3
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(201)
			->assertJson([
				'success' => true,
				'message' => 'Todo list created successfully'
			])
			->assertJsonStructure([
				'success',
				'message',
				'data' => [
					'id',
					'title',
					'assigne',
					'due_date',
					'time_tracked',
					'status',
					'priority',
					'type',
					'estimated_sp',
					'actual_sp',
					'created_at',
					'updated_at'
				]
			]);

		// Assert data is stored in database
		$this->assertDatabaseHas('todo_lists', [
			'title' => 'Complete project documentation',
			'assigne' => 'John Doe',
			'due_date' => '2025-09-15',
			'status' => 'in_progress',
			'priority' => 'high'
		]);
	}

	/**
	 * Test successful todo list creation with minimal data (using defaults).
	 */
	public function test_store_todo_list_success_with_minimal_data(): void
	{
		$todoData = [
			'task' => 'Simple task'
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(201)
			->assertJson([
				'success' => true,
				'message' => 'Todo list created successfully'
			]);

		// Assert defaults are applied
		$this->assertDatabaseHas('todo_lists', [
			'title' => 'Simple task',
			'due_date' => now()->format('Y-m-d'), // Should default to today
			'status' => 'pending' // Should default to pending
		]);
	}

	/**
	 * Test todo list creation with only title and due_date.
	 */
	public function test_store_todo_list_success_with_partial_data(): void
	{
		$todoData = [
			'task' => 'Review code',
			'due_date' => '2025-09-20'
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(201)
			->assertJson([
				'success' => true,
				'message' => 'Todo list created successfully'
			]);

		// Assert data is stored correctly with defaults where needed
		$this->assertDatabaseHas('todo_lists', [
			'title' => 'Review code',
			'due_date' => '2025-09-20',
			'status' => 'pending' // Should default to pending
		]);
	}

	/**
	 * Test validation error when due_date is in the past.
	 */
	public function test_store_todo_list_fails_with_past_due_date(): void
	{
		$todoData = [
			'task' => 'Past task',
			'due_date' => '2025-09-01' // Past date
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(422)
			->assertJson([
				'success' => false,
				'message' => 'Validation failed'
			])
			->assertJsonValidationErrors(['due_date']);
	}

	/**
	 * Test validation error with invalid status.
	 */
	public function test_store_todo_list_fails_with_invalid_status(): void
	{
		$todoData = [
			'task' => 'Invalid status task',
			'status' => 'invalid_status'
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(422)
			->assertJson([
				'success' => false,
				'message' => 'Validation failed'
			])
			->assertJsonValidationErrors(['status']);
	}

	/**
	 * Test validation error with invalid priority.
	 */
	public function test_store_todo_list_fails_with_invalid_priority(): void
	{
		$todoData = [
			'task' => 'Invalid priority task',
			'priority' => 'invalid_priority'
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(422)
			->assertJson([
				'success' => false,
				'message' => 'Validation failed'
			])
			->assertJsonValidationErrors(['priority']);
	}

	/**
	 * Test validation error with invalid type.
	 */
	public function test_store_todo_list_fails_with_invalid_type(): void
	{
		$todoData = [
			'task' => 'Invalid type task',
			'type' => 'invalid_type'
		];

		$response = $this->postJson('/api/todo-lists', $todoData);

		$response->assertStatus(422)
			->assertJson([
				'success' => false,
				'message' => 'Validation failed'
			])
			->assertJsonValidationErrors(['type']);
	}

	/**
	 * Test successful todo list creation with new priority values.
	 */
	public function test_store_todo_list_success_with_new_priority_values(): void
	{
		// Test critical priority
		$todoData1 = [
			'task' => 'Critical Task',
			'priority' => 'critical',
			'due_date' => '2025-09-15'
		];

		$response1 = $this->postJson('/api/todo-lists', $todoData1);

		$response1->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Todo list created successfully'
				]);

		$this->assertDatabaseHas('todo_lists', [
			'title' => 'Critical Task',
			'priority' => 'critical'
		]);

		// Test best_effort priority
		$todoData2 = [
			'task' => 'Best Effort Task',
			'priority' => 'best_effort',
			'due_date' => '2025-09-16'
		];

		$response2 = $this->postJson('/api/todo-lists', $todoData2);

		$response2->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Todo list created successfully'
				]);

		$this->assertDatabaseHas('todo_lists', [
			'title' => 'Best Effort Task',
			'priority' => 'best_effort'
		]);
	}
}
