<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\Service;

use DateTime;
use DateTimeInterface;
use OCA\EisenhowerBoard\Db\TaskMapper;
use OCA\EisenhowerBoard\Entity\Task;
use OCP\IL10N;

/**
 * Service layer for task management.
 *
 * Provides business logic for task operations including validation
 * and position calculation.
 */
class TaskService {
	private TaskMapper $taskMapper;
	private IL10N $l10n;

	public function __construct(TaskMapper $taskMapper, IL10N $l10n) {
		$this->taskMapper = $taskMapper;
		$this->l10n = $l10n;
	}

	/**
	 * Get all tasks for the current user.
	 *
	 * @param string $userId User ID
	 * @return Task[] Array of Task entities
	 */
	public function getAllTasks(string $userId): array {
		return $this->taskMapper->findAllByUser($userId);
	}

	/**
	 * Get a specific task by ID.
	 *
	 * @param int $id Task ID
	 * @param string $userId User ID
	 * @return Task|null Task entity or null if not found
	 */
	public function getTask(int $id, string $userId): ?Task {
		return $this->taskMapper->find($id, $userId);
	}

	/**
	 * Create a new task.
	 *
	 * @param string $userId User ID
	 * @param string $description Task description
	 * @param int $importance Importance score (0-100)
	 * @param DateTimeInterface $dueDate Due date
	 * @return Task Created task
	 * @throws \InvalidArgumentException If validation fails
	 */
	public function createTask(
		string $userId,
		string $description,
		int $importance,
		DateTimeInterface $dueDate
	): Task {
		$this->validateTask($description, $importance, $dueDate);

		$task = new Task(
			null,
			$userId,
			$description,
			$importance,
			$dueDate
		);

		return $this->taskMapper->insert($task);
	}

	/**
	 * Update an existing task.
	 *
	 * @param int $id Task ID
	 * @param string $userId User ID
	 * @param string|null $description Task description (null to keep existing)
	 * @param int|null $importance Importance score (null to keep existing)
	 * @param DateTimeInterface|null $dueDate Due date (null to keep existing)
	 * @return Task|null Updated task or null if not found
	 * @throws \InvalidArgumentException If validation fails
	 */
	public function updateTask(
		int $id,
		string $userId,
		?string $description = null,
		?int $importance = null,
		?DateTimeInterface $dueDate = null
	): ?Task {
		$task = $this->taskMapper->find($id, $userId);

		if ($task === null) {
			return null;
		}

		// Update fields if provided
		if ($description !== null) {
			$task->setDescription($description);
		}

		if ($importance !== null) {
			$task->setImportance($importance);
		}

		if ($dueDate !== null) {
			$task->setDueDate($dueDate);
		}

		// Validate the updated task
		$this->validateTask($task->getDescription(), $task->getImportance(), $task->getDueDate());

		return $this->taskMapper->update($task);
	}

	/**
	 * Delete a task.
	 *
	 * @param int $id Task ID
	 * @param string $userId User ID
	 * @return bool True if task was deleted
	 */
	public function deleteTask(int $id, string $userId): bool {
		return $this->taskMapper->delete($id, $userId);
	}

	/**
	 * Calculate the X position for a task on the board.
	 *
	 * Uses logarithmic scale: x = log(daysUntilDue + 1) * scale
	 * This compresses far-future dates and spreads out near-term dates.
	 *
	 * @param Task $task Task entity
	 * @param float $scale Scaling factor (default: 150)
	 * @param DateTimeInterface|null $now Reference date (defaults to now)
	 * @return float X coordinate
	 */
	public function calculateXPosition(
		Task $task,
		float $scale = 150.0,
		?DateTimeInterface $now = null
	): float {
		$now = $now ?? new DateTime();
		$dueDate = $task->getDueDate();

		// Calculate days until due using timestamps for accuracy
		// DateInterval::format('%r%a') only counts full days, so we use timestamp difference
		$timestampNow = $now->getTimestamp();
		$timestampDue = $dueDate->getTimestamp();
		$secondsUntilDue = $timestampDue - $timestampNow;
		$daysUntilDue = $secondsUntilDue / (24 * 60 * 60); // Convert to days

		// Use absolute value for log, then restore sign
		$absDays = abs($daysUntilDue);
		$logScale = log($absDays + 1); // +1 prevents log(0)

		// If overdue, position to the left
		if ($daysUntilDue < 0) {
			$logScale = -$logScale;
		}

		return $logScale * $scale;
	}

	/**
	 * Calculate the Y position for a task on the board.
	 *
	 * Uses linear scale: y maps importance (0-100) to board height.
	 * Higher importance = higher on the board (top).
	 *
	 * @param Task $task Task entity
	 * @param float $boardHeight Board height in pixels
	 * @param float $padding Padding from top and bottom
	 * @return float Y coordinate
	 */
	public function calculateYPosition(
		Task $task,
		float $boardHeight = 800.0,
		float $padding = 40.0
	): float {
		$importance = $task->getImportance();
		// Map: 100 importance -> top padding, 0 importance -> bottom (height - padding)
		return $padding + ((100 - $importance) / 100) * ($boardHeight - 2 * $padding);
	}

	/**
	 * Get position for multiple tasks at once.
	 *
	 * @param Task[] $tasks Array of Task entities
	 * @param float $scale X-axis scaling factor
	 * @param float $boardHeight Board height in pixels
	 * @param float $padding Board padding
	 * @param DateTimeInterface|null $now Reference date
	 * @return array<int, array{x: float, y: float}> Array of positions indexed by task ID
	 */
	public function calculatePositions(
		array $tasks,
		float $scale = 150.0,
		float $boardHeight = 800.0,
		float $padding = 40.0,
		?DateTimeInterface $now = null
	): array {
		$positions = [];
		$now = $now ?? new DateTime();

		foreach ($tasks as $task) {
			$positions[$task->getId() ?? 0] = [
				'x' => $this->calculateXPosition($task, $scale, $now),
				'y' => $this->calculateYPosition($task, $boardHeight, $padding),
			];
		}

		return $positions;
	}

	/**
	 * Validate task data before creation or update.
	 *
	 * @param string $description Task description
	 * @param int $importance Importance score
	 * @param DateTimeInterface $dueDate Due date
	 * @throws \InvalidArgumentException If validation fails
	 */
	private function validateTask(
		string $description,
		int $importance,
		DateTimeInterface $dueDate
	): void {
		// Validate description
		if (trim($description) === '') {
			throw new \InvalidArgumentException(
				$this->l10n->t('Task description cannot be empty')
			);
		}

		if (strlen($description) > 10000) {
			throw new \InvalidArgumentException(
				$this->l10n->t('Task description is too long (max 10000 characters)')
			);
		}

		// Validate importance
		if ($importance < 0 || $importance > 100) {
			throw new \InvalidArgumentException(
				$this->l10n->t('Importance must be between 0 and 100')
			);
		}

		// Validate due date
		$now = new DateTime();
		// We allow past dates (overdue tasks)
		// Just ensure it's a valid date
		// The DateTimeInterface type already ensures this
	}
}
