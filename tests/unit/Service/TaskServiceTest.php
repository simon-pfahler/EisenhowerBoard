<?php

declare(strict_types=1);

namespace Unit\Service;

use DateTime;
use OCA\EisenhowerBoard\Db\TaskMapper;
use OCA\EisenhowerBoard\Entity\Task;
use OCA\EisenhowerBoard\Service\TaskService;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

final class TaskServiceTest extends TestCase {
	private TaskService $service;
	private TaskMapper $mapper;

	protected function setUp(): void {
		$this->mapper = $this->createMock(TaskMapper::class);
		$l10n = $this->createMock(IL10N::class);
		// Configure l10n to return the message as-is
		$l10n->method('t')->willReturnArgument(0);

		$this->service = new TaskService($this->mapper, $l10n);
	}

	public function testGetAllTasks(): void {
		$tasks = [
			new Task(1, 'user1', 'Task 1', 50, new DateTime('+1 day')),
			new Task(2, 'user1', 'Task 2', 75, new DateTime('+2 days')),
		];

		$this->mapper->method('findAllByUser')->willReturn($tasks);

		$result = $this->service->getAllTasks('user1');

		$this->assertCount(2, $result);
		$this->assertSame($tasks, $result);
	}

	public function testGetTask(): void {
		$task = new Task(1, 'user1', 'Task 1', 50, new DateTime('+1 day'));

		$this->mapper->method('find')->willReturn($task);

		$result = $this->service->getTask(1, 'user1');

		$this->assertSame($task, $result);
	}

	public function testGetTaskNotFound(): void {
		$this->mapper->method('find')->willReturn(null);

		$result = $this->service->getTask(999, 'user1');

		$this->assertNull($result);
	}

	public function testCreateTask(): void {
		$dueDate = new DateTime('+1 day');
		$createdTask = new Task(1, 'user1', 'New task', 50, $dueDate);

		$this->mapper->method('insert')->willReturn($createdTask);

		$result = $this->service->createTask('user1', 'New task', 50, $dueDate);

		$this->assertSame($createdTask, $result);
	}

	public function testCreateTaskEmptyDescription(): void {
		$dueDate = new DateTime('+1 day');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Task description cannot be empty');

		$this->service->createTask('user1', '', 50, $dueDate);
	}

	public function testCreateTaskTooLongDescription(): void {
		$dueDate = new DateTime('+1 day');
		$longDescription = str_repeat('a', 10001);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Task description is too long (max 10000 characters)');

		$this->service->createTask('user1', $longDescription, 50, $dueDate);
	}

	public function testCreateTaskInvalidImportanceHigh(): void {
		$dueDate = new DateTime('+1 day');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Importance must be between 0 and 100');

		$this->service->createTask('user1', 'Task', 101, $dueDate);
	}

	public function testCreateTaskInvalidImportanceLow(): void {
		$dueDate = new DateTime('+1 day');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Importance must be between 0 and 100');

		$this->service->createTask('user1', 'Task', -1, $dueDate);
	}

	public function testUpdateTask(): void {
		$existingTask = new Task(1, 'user1', 'Old task', 50, new DateTime('+1 day'));
		$updatedTask = new Task(1, 'user1', 'Updated task', 75, new DateTime('+2 days'));

		$this->mapper->method('find')->willReturn($existingTask);
		$this->mapper->method('update')->willReturn($updatedTask);

		$result = $this->service->updateTask(1, 'user1', 'Updated task', 75, new DateTime('+2 days'));

		$this->assertSame($updatedTask, $result);
	}

	public function testUpdateTaskNotFound(): void {
		$this->mapper->method('find')->willReturn(null);

		$result = $this->service->updateTask(999, 'user1', 'Updated task');

		$this->assertNull($result);
	}

	public function testUpdateTaskPartial(): void {
		$existingTask = new Task(1, 'user1', 'Old task', 50, new DateTime('+1 day'));
		$updatedTask = new Task(1, 'user1', 'Updated task', 50, new DateTime('+1 day'));

		$this->mapper->method('find')->willReturn($existingTask);
		$this->mapper->method('update')->willReturn($updatedTask);

		// Only update description, leave other fields unchanged
		$result = $this->service->updateTask(1, 'user1', 'Updated task');

		$this->assertSame($updatedTask, $result);
	}

	public function testDeleteTask(): void {
		$this->mapper->method('delete')->willReturn(true);

		$result = $this->service->deleteTask(1, 'user1');

		$this->assertTrue($result);
	}

	public function testDeleteTaskNotFound(): void {
		$this->mapper->method('delete')->willReturn(false);

		$result = $this->service->deleteTask(999, 'user1');

		$this->assertFalse($result);
	}

	public function testCalculateXPosition(): void {
		$task = new Task(1, 'user1', 'Task', 50, new DateTime('+1 day'));

		$x = $this->service->calculateXPosition($task);

		// Task due in 1 day should have a positive x value
		$this->assertGreaterThan(0, $x);
	}

	public function testCalculateXPositionScale(): void {
		$task = new Task(1, 'user1', 'Task', 50, new DateTime('+10 days'));

		$x1 = $this->service->calculateXPosition($task, 100.0);
		$x2 = $this->service->calculateXPosition($task, 200.0);

		// Larger scale should result in larger x value
		$this->assertGreaterThan($x1, $x2);
	}

	public function testCalculateYPosition(): void {
		$task = new Task(1, 'user1', 'Task', 50, new DateTime('+1 day'));

		$y = $this->service->calculateYPosition($task, 800, 40);

		// Importance 50 should be in the middle of the board
		// With 800px height and 40px padding, middle is around 440
		$this->assertGreaterThan(400, $y);
		$this->assertLessThan(500, $y);
	}

	public function testCalculateYPositionTop(): void {
		$task = new Task(1, 'user1', 'Task', 100, new DateTime('+1 day'));

		$y = $this->service->calculateYPosition($task, 800, 40);

		// Importance 100 should be at the top (small y value, near padding)
		$this->assertGreaterThanOrEqual(40, $y);
		$this->assertLessThan(100, $y);
	}

	public function testCalculateYPositionBottom(): void {
		$task = new Task(1, 'user1', 'Task', 0, new DateTime('+1 day'));

		$y = $this->service->calculateYPosition($task, 800, 40);

		// Importance 0 should be at the bottom (large y value, near height - padding)
		$this->assertGreaterThan(700, $y);
		$this->assertLessThanOrEqual(760, $y);
	}

	public function testCalculatePositions(): void {
		$tasks = [
			new Task(1, 'user1', 'Task 1', 50, new DateTime('+1 day')),
			new Task(2, 'user1', 'Task 2', 75, new DateTime('+2 days')),
		];

		$positions = $this->service->calculatePositions($tasks);

		$this->assertCount(2, $positions);
		$this->assertArrayHasKey(1, $positions);
		$this->assertArrayHasKey(2, $positions);
		$this->assertArrayHasKey('x', $positions[1]);
		$this->assertArrayHasKey('y', $positions[1]);
	}
}
