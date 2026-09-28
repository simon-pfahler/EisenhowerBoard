<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\Db;

use DateTime;
use OCA\EisenhowerBoard\Entity\Task;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Database mapper for Task entities.
 *
 * Handles all database operations for tasks including CRUD operations.
 */
class TaskMapper {
	private IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * Get all tasks for a specific user.
	 *
	 * @param string $userId Nextcloud user ID
	 * @return Task[] Array of Task entities
	 */
	public function findAllByUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('eisenhowerboard_tasks')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->orderBy('due_date', 'ASC');

		$results = $qb->executeQuery();
		$tasks = [];

		while ($row = $results->fetchAssociative()) {
			$tasks[] = Task::fromDatabaseRow($row);
		}

		return $tasks;
	}

	/**
	 * Find a specific task by ID.
	 *
	 * @param int $id Task ID
	 * @param string $userId User ID (for access control)
	 * @return Task|null Task entity or null if not found
	 */
	public function find(int $id, string $userId): ?Task {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('eisenhowerboard_tasks')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$result = $qb->executeQuery();
		$row = $result->fetchAssociative();

		if ($row === false) {
			return null;
		}

		return Task::fromDatabaseRow($row);
	}

	/**
	 * Find a task by ID without checking user (use with caution).
	 *
	 * @param int $id Task ID
	 * @return Task|null Task entity or null if not found
	 */
	public function findById(int $id): ?Task {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('eisenhowerboard_tasks')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$result = $qb->executeQuery();
		$row = $result->fetchAssociative();

		if ($row === false) {
			return null;
		}

		return Task::fromDatabaseRow($row);
	}

	/**
	 * Insert a new task into the database.
	 *
	 * @param Task $task Task entity to insert
	 * @return Task Task with updated ID
	 */
	public function insert(Task $task): Task {
		$qb = $this->db->getQueryBuilder();

		$qb->insert('eisenhowerboard_tasks')
			->values([
				'user_id' => $qb->createNamedParameter($task->getUserId()),
				'description' => $qb->createNamedParameter($task->getDescription()),
				'importance' => $qb->createNamedParameter($task->getImportance()),
				'due_date' => $qb->createNamedParameter($task->getDueDate(), IQueryBuilder::PARAM_DATE),
				'created_at' => $qb->createNamedParameter($task->getCreatedAt(), IQueryBuilder::PARAM_DATE),
				'updated_at' => $qb->createNamedParameter($task->getUpdatedAt(), IQueryBuilder::PARAM_DATE),
			]);

		$qb->executeStatement();

		// Get the inserted ID
		$insertId = (int) $qb->getLastInsertId();

		// Return a new Task instance with the ID
		return new Task(
			id: $insertId,
			userId: $task->getUserId(),
			description: $task->getDescription(),
			importance: $task->getImportance(),
			dueDate: $task->getDueDate(),
			createdAt: $task->getCreatedAt(),
			updatedAt: $task->getUpdatedAt(),
		);
	}

	/**
	 * Update an existing task.
	 *
	 * @param Task $task Task entity to update
	 * @return Task Updated task
	 */
	public function update(Task $task): Task {
		$qb = $this->db->getQueryBuilder();

		$qb->update('eisenhowerboard_tasks')
			->set('description', $qb->createNamedParameter($task->getDescription()))
			->set('importance', $qb->createNamedParameter($task->getImportance()))
			->set('due_date', $qb->createNamedParameter($task->getDueDate(), IQueryBuilder::PARAM_DATE))
			->set('updated_at', $qb->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($task->getId())));

		$qb->executeStatement();

		return $task;
	}

	/**
	 * Delete a task.
	 *
	 * @param int $id Task ID
	 * @param string $userId User ID (for access control)
	 * @return bool True if task was deleted
	 */
	public function delete(int $id, string $userId): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->delete('eisenhowerboard_tasks')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$result = $qb->executeStatement();

		return $result > 0;
	}

	/**
	 * Delete all tasks for a specific user.
	 *
	 * @param string $userId User ID
	 * @return int Number of tasks deleted
	 */
	public function deleteAllByUser(string $userId): int {
		$qb = $this->db->getQueryBuilder();

		$qb->delete('eisenhowerboard_tasks')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		return $qb->executeStatement();
	}
}
