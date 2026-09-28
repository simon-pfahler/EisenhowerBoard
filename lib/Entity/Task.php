<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\Entity;

use DateTime;
use DateTimeInterface;

/**
 * Task entity for the Eisenhower Board app.
 *
 * Represents a single task with description, importance, and due date.
 * Tasks are automatically positioned on a 2D board based on importance (y-axis)
 * and due date (x-axis using logarithmic scale).
 */
class Task {
	private ?int $id;
	private string $userId;
	private string $description;
	private int $importance;
	private DateTimeInterface $dueDate;
	private DateTimeInterface $createdAt;
	private DateTimeInterface $updatedAt;

	/**
	 * @param int|null $id Task ID (null for new tasks)
	 * @param string $userId Nextcloud user ID
	 * @param string $description Task description
	 * @param int $importance Importance score (0-100)
	 * @param DateTimeInterface $dueDate When the task is due
	 * @param DateTimeInterface|null $createdAt When the task was created
	 * @param DateTimeInterface|null $updatedAt When the task was last updated
	 */
	public function __construct(
		?int $id,
		string $userId,
		string $description,
		int $importance,
		DateTimeInterface $dueDate,
		?DateTimeInterface $createdAt = null,
		?DateTimeInterface $updatedAt = null
	) {
		$this->id = $id;
		$this->userId = $userId;
		$this->description = $description;
		$this->importance = $importance;
		$this->dueDate = $dueDate;
		$this->createdAt = $createdAt ?? new DateTime();
		$this->updatedAt = $updatedAt ?? new DateTime();
	}

	// Region: Getters

	public function getId(): ?int {
		return $this->id;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getImportance(): int {
		return $this->importance;
	}

	public function getDueDate(): DateTimeInterface {
		return $this->dueDate;
	}

	public function getCreatedAt(): DateTimeInterface {
		return $this->createdAt;
	}

	public function getUpdatedAt(): DateTimeInterface {
		return $this->updatedAt;
	}

	// Endregion: Getters

	// Region: Setters

	public function setDescription(string $description): void {
		$this->description = $description;
	}

	public function setImportance(int $importance): void {
		if ($importance < 0 || $importance > 100) {
			throw new \InvalidArgumentException('Importance must be between 0 and 100');
		}
		$this->importance = $importance;
	}

	public function setDueDate(DateTimeInterface $dueDate): void {
		$this->dueDate = $dueDate;
	}

	public function setUpdatedAt(DateTimeInterface $updatedAt): void {
		$this->updatedAt = $updatedAt;
	}

	// Endregion: Setters

	/**
	 * Calculate X position for the board using logarithmic scale.
	 *
	 * X = log(daysUntilDue + 1) * scale_factor
	 * This compresses the time scale so tasks due soon are spread out
	 * and tasks due far in the future are compressed.
	 *
	 * @param DateTimeInterface|null $now Optional reference date (defaults to now)
	 * @return float X coordinate value
	 */
	public function calculateX(?DateTimeInterface $now = null): float {
		$now = $now ?? new DateTime();
		$dueDate = $this->getDueDate();

		// Calculate days until due (can be negative for overdue tasks)
		$interval = $dueDate->diff($now);
		$daysUntilDue = (float) $interval->format('%r%a'); // Signed days

		// Use log(days + 1) to handle due dates at 0 or negative
		// +1 prevents log(0) which is undefined
		$logScale = log(abs($daysUntilDue) + 1);

		// If task is overdue (negative days), position it to the left of 0
		if ($daysUntilDue < 0) {
			$logScale = -$logScale;
		}

		// Scale factor to spread tasks across the board
		// Adjust this based on your board width
		return $logScale * 150.0;
	}

	/**
	 * Calculate Y position for the board using linear scale.
	 *
	 * Y = 100 - importance (so 100 is at top, 0 at bottom)
	 *
	 * @param float $boardHeight Board height in pixels
	 * @param float $padding Padding from edges
	 * @return float Y coordinate value
	 */
	public function calculateY(float $boardHeight = 800.0, float $padding = 40.0): float {
		$importance = $this->getImportance();
		// Map 0-100 importance to board height with padding
		// 100 importance -> top (padding)
		// 0 importance -> bottom (boardHeight - padding)
		$y = $padding + ((100 - $importance) / 100) * ($boardHeight - 2 * $padding);
		return $y;
	}

	/**
	 * Get task data as an array (for API responses).
	 *
	 * @return array{id: int|null, user_id: string, description: string, importance: int, due_date: string, created_at: string, updated_at: string}
	 */
	public function toArray(): array {
		return [
			'id' => $this->id,
			'user_id' => $this->userId,
			'description' => $this->description,
			'importance' => $this->importance,
			'due_date' => $this->dueDate->format(DateTimeInterface::ATOM),
			'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
			'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
		];
	}

	/**
	 * Create a Task from a database row.
	 *
	 * @param array $row Database row with task data
	 * @return Task
	 */
	public static function fromDatabaseRow(array $row): Task {
		return new Task(
			id: (int) ($row['id'] ?? null),
			userId: $row['user_id'],
			description: $row['description'],
			importance: (int) $row['importance'],
			dueDate: new DateTime($row['due_date']),
			createdAt: new DateTime($row['created_at']),
			updatedAt: new DateTime($row['updated_at']),
		);
	}
}
