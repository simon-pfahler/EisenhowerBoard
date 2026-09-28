<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\Controller;

use DateTime;
use OCA\EisenhowerBoard\Entity\Task;
use OCA\EisenhowerBoard\Service\TaskService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\AppFramework\OCSController;

/**
 * API Controller for managing tasks in the Eisenhower Board.
 */
class ApiController extends OCSController {
	private TaskService $taskService;

	public function __construct(string $appName, \OCP\IRequest $request, TaskService $taskService) {
		parent::__construct($appName, $request);
		$this->taskService = $taskService;
	}

	/**
	 * List all tasks for the current user.
	 *
	 * @return DataResponse<Http::STATUS_OK, array<int, array{id: int, user_id: string, description: string, importance: int, due_date: string, created_at: string, updated_at: string}>, array{}>
	 *
	 * 200: List of tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/tasks')]
	public function listTasks(): DataResponse {
		$userId = $this->userId;
		$tasks = $this->taskService->getAllTasks($userId);

		$taskData = array_map(function (Task $task) {
			return $task->toArray();
		}, $tasks);

		return new DataResponse($taskData);
	}

	/**
	 * Create a new task.
	 *
	 * @param string $description Task description
	 * @param int $importance Importance score (0-100)
	 * @param string $dueDate Due date (ISO 8601 format)
	 * @return DataResponse<Http::STATUS_CREATED, array{id: int, user_id: string, description: string, importance: int, due_date: string, created_at: string, updated_at: string}, array{}>
	 *
	 * 201: Task created
	 * 400: Invalid input data
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/tasks')]
	public function createTask(
		string $description,
		int $importance,
		string $dueDate
	): DataResponse {
		$userId = $this->userId;

		try {
			$dueDateTime = new DateTime($dueDate);
		} catch (\Exception $e) {
			return new DataResponse(
				['error' => 'Invalid due date format. Use ISO 8601 format (e.g., 2025-01-01T12:00:00)'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$task = $this->taskService->createTask(
				$userId,
				$description,
				$importance,
				$dueDateTime
			);

			return new DataResponse(
				$task->toArray(),
				Http::STATUS_CREATED
			);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * Get a specific task by ID.
	 *
	 * @param int $id Task ID
	 * @return DataResponse<Http::STATUS_OK, array{id: int, user_id: string, description: string, importance: int, due_date: string, created_at: string, updated_at: string}, array{}>
	 *
	 * 200: Task found
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/tasks/{id}')]
	public function getTask(int $id): DataResponse {
		$userId = $this->userId;
		$task = $this->taskService->getTask($id, $userId);

		if ($task === null) {
			return new DataResponse(
				['error' => 'Task not found'],
				Http::STATUS_NOT_FOUND
			);
		}

		return new DataResponse($task->toArray());
	}

	/**
	 * Update an existing task.
	 *
	 * All parameters are optional. Only provided fields will be updated.
	 *
	 * @param int $id Task ID
	 * @param string|null $description New description
	 * @param int|null $importance New importance score
	 * @param string|null $dueDate New due date (ISO 8601 format)
	 * @return DataResponse<Http::STATUS_OK, array{id: int, user_id: string, description: string, importance: int, due_date: string, created_at: string, updated_at: string}, array{}>
	 *
	 * 200: Task updated
	 * 400: Invalid input data
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/tasks/{id}')]
	public function updateTask(
		int $id,
		?string $description = null,
		?int $importance = null,
		?string $dueDate = null
	): DataResponse {
		$userId = $this->userId;

		// Convert due date string to DateTime if provided
		$dueDateTime = null;
		if ($dueDate !== null) {
			try {
				$dueDateTime = new DateTime($dueDate);
			} catch (\Exception $e) {
				return new DataResponse(
					['error' => 'Invalid due date format. Use ISO 8601 format'],
					Http::STATUS_BAD_REQUEST
				);
			}
		}

		try {
			$task = $this->taskService->updateTask(
				$id,
				$userId,
				$description,
				$importance,
				$dueDateTime
			);

			if ($task === null) {
				return new DataResponse(
					['error' => 'Task not found'],
					Http::STATUS_NOT_FOUND
				);
			}

			return new DataResponse($task->toArray());
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * Delete a task.
	 *
	 * @param int $id Task ID
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Task deleted
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/tasks/{id}')]
	public function deleteTask(int $id): DataResponse {
		$userId = $this->userId;
		$deleted = $this->taskService->deleteTask($id, $userId);

		if (!$deleted) {
			return new DataResponse(
				['error' => 'Task not found'],
				Http::STATUS_NOT_FOUND
			);
		}

		return new DataResponse(['message' => 'Task deleted']);
	}
}
