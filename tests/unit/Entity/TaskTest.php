<?php

declare(strict_types=1);

namespace Unit\Entity;

use DateTime;
use OCA\EisenhowerBoard\Entity\Task;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase {
	public function testGettersAndSetters(): void {
		$now = new DateTime();
		$dueDate = new DateTime('2025-12-31');

		$task = new Task(
			id: 1,
			userId: 'test-user',
			description: 'Test task',
			importance: 75,
			dueDate: $dueDate,
			createdAt: $now,
			updatedAt: $now
		);

		// Test getters
		$this->assertSame(1, $task->getId());
		$this->assertSame('test-user', $task->getUserId());
		$this->assertSame('Test task', $task->getDescription());
		$this->assertSame(75, $task->getImportance());
		$this->assertSame($dueDate, $task->getDueDate());
		$this->assertSame($now, $task->getCreatedAt());
		$this->assertSame($now, $task->getUpdatedAt());

		// Test setters
		$newDescription = 'Updated description';
		$task->setDescription($newDescription);
		$this->assertSame($newDescription, $task->getDescription());

		$newImportance = 90;
		$task->setImportance($newImportance);
		$this->assertSame($newImportance, $task->getImportance());

		$newDueDate = new DateTime('2026-01-01');
		$task->setDueDate($newDueDate);
		$this->assertSame($newDueDate, $task->getDueDate());

		$newUpdatedAt = new DateTime('2025-01-01');
		$task->setUpdatedAt($newUpdatedAt);
		$this->assertSame($newUpdatedAt, $task->getUpdatedAt());
	}

	public function testImportanceValidation(): void {
		$task = new Task(
			id: null,
			userId: 'test-user',
			description: 'Test',
			importance: 50,
			dueDate: new DateTime()
		);

		// Valid importance values
		$task->setImportance(0);
		$this->assertSame(0, $task->getImportance());

		$task->setImportance(100);
		$this->assertSame(100, $task->getImportance());

		$task->setImportance(50);
		$this->assertSame(50, $task->getImportance());

		// Invalid importance values should throw exception
		$this->expectException(\InvalidArgumentException::class);
		$task->setImportance(101);
	}

	public function testImportanceValidationNegative(): void {
		$task = new Task(
			id: null,
			userId: 'test-user',
			description: 'Test',
			importance: 50,
			dueDate: new DateTime()
		);

		$this->expectException(\InvalidArgumentException::class);
		$task->setImportance(-1);
	}

	public function testCalculateXPosition(): void {
		$now = new DateTime();

		// Task due in 1 day
		$dueDate1 = new DateTime('+1 day');
		$task1 = new Task(null, 'user', 'Task 1', 50, $dueDate1);
		$x1 = $task1->calculateX($now);

		// Task due in 10 days
		$dueDate2 = new DateTime('+10 days');
		$task2 = new Task(null, 'user', 'Task 2', 50, $dueDate2);
		$x2 = $task2->calculateX($now);

		// Task due in 100 days
		$dueDate3 = new DateTime('+100 days');
		$task3 = new Task(null, 'user', 'Task 3', 50, $dueDate3);
		$x3 = $task3->calculateX($now);

		// Tasks due sooner should have smaller x (more to the left)
		// With logarithmic scale, tasks with shorter due dates are more spread out
		$this->assertLessThan($x2, $x1);
		$this->assertLessThan($x3, $x2);

		// For logarithmic scale, the gaps actually increase between consecutive
		// orders of magnitude because log is concave (second derivative is negative)
		// So gap2 should be greater than gap1
		$gap1 = $x2 - $x1;
		$gap2 = $x3 - $x2;
		$this->assertGreaterThan($gap1, $gap2);
	}

	public function testCalculateXPositionOverdue(): void {
		$now = new DateTime();

		// Task due 1 day ago (overdue)
		$dueDate = new DateTime('-1 day');
		$task = new Task(null, 'user', 'Overdue task', 50, $dueDate);
		$x = $task->calculateX();

		// Overdue tasks should have negative X (to the left of 0)
		$this->assertLessThan(0, $x);
	}

	public function testCalculateYPosition(): void {
		$dueDate = new DateTime('+1 day');

		// Task with importance 0 (bottom)
		$task0 = new Task(null, 'user', 'Task 0', 0, $dueDate);
		$y0 = $task0->calculateY(800, 40);

		// Task with importance 100 (top)
		$task100 = new Task(null, 'user', 'Task 100', 100, $dueDate);
		$y100 = $task100->calculateY(800, 40);

		// Task with importance 50 (middle)
		$task50 = new Task(null, 'user', 'Task 50', 50, $dueDate);
		$y50 = $task50->calculateY(800, 40);

		// Importance 100 should be at the top (smaller y value)
		// Importance 0 should be at the bottom (larger y value)
		$this->assertLessThan($y50, $y100);
		$this->assertGreaterThan($y50, $y0);

		// Y values should be within board bounds (40 to 760 for 800px height with 40px padding)
		$this->assertGreaterThanOrEqual(40, $y0);
		$this->assertLessThanOrEqual(760, $y0);
		$this->assertGreaterThanOrEqual(40, $y100);
		$this->assertLessThanOrEqual(760, $y100);
	}

	public function testToArray(): void {
		$dueDate = new DateTime('2025-12-31T12:00:00');
		$createdAt = new DateTime('2025-01-01T00:00:00');
		$updatedAt = new DateTime('2025-01-02T00:00:00');

		$task = new Task(
			id: 1,
			userId: 'test-user',
			description: 'Test task',
			importance: 75,
			dueDate: $dueDate,
			createdAt: $createdAt,
			updatedAt: $updatedAt
		);

		$array = $task->toArray();

		$this->assertSame(1, $array['id']);
		$this->assertSame('test-user', $array['user_id']);
		$this->assertSame('Test task', $array['description']);
		$this->assertSame(75, $array['importance']);
		$this->assertSame('2025-12-31T12:00:00+00:00', $array['due_date']);
		$this->assertSame('2025-01-01T00:00:00+00:00', $array['created_at']);
		$this->assertSame('2025-01-02T00:00:00+00:00', $array['updated_at']);
	}

	public function testFromDatabaseRow(): void {
		$row = [
			'id' => 1,
			'user_id' => 'test-user',
			'description' => 'Test task',
			'importance' => 75,
			'due_date' => '2025-12-31 12:00:00',
			'created_at' => '2025-01-01 00:00:00',
			'updated_at' => '2025-01-02 00:00:00',
		];

		$task = Task::fromDatabaseRow($row);

		$this->assertSame(1, $task->getId());
		$this->assertSame('test-user', $task->getUserId());
		$this->assertSame('Test task', $task->getDescription());
		$this->assertSame(75, $task->getImportance());
		$this->assertInstanceOf(DateTime::class, $task->getDueDate());
		$this->assertSame('2025-12-31 12:00:00', $task->getDueDate()->format('Y-m-d H:i:s'));
	}

	public function testNewTaskHasTimestamps(): void {
		$dueDate = new DateTime('2025-12-31');
		$before = new DateTime();
		$task = new Task(
			id: null,
			userId: 'test-user',
			description: 'Test',
			importance: 50,
			dueDate: $dueDate
		);
		$after = new DateTime();

		$createdAt = $task->getCreatedAt();
		$updatedAt = $task->getUpdatedAt();

		// Timestamps should be between before and after
		$this->assertGreaterThanOrEqual($before, $createdAt);
		$this->assertLessThanOrEqual($after, $createdAt);
		$this->assertGreaterThanOrEqual($before, $updatedAt);
		$this->assertLessThanOrEqual($after, $updatedAt);
	}
}
