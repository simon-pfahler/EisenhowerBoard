<?php

declare(strict_types=1);

namespace Controller;

use DateTime;
use OCA\EisenhowerBoard\AppInfo\Application;
use OCA\EisenhowerBoard\Controller\ApiController;
use OCA\EisenhowerBoard\Service\TaskService;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase {
	private ApiController $controller;
	private TaskService $taskService;
	private IRequest $request;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getUserId')->willReturn('test-user');

		$this->taskService = $this->createMock(TaskService::class);

		$l10n = $this->createMock(IL10N::class);

		// Configure TaskService mock for different scenarios
		$this->controller = new ApiController(
			Application::APP_ID,
			$this->request,
			$this->taskService
		);
	}

	public function testListTasks(): void {
		// Configure mock to return empty array
		$this->taskService->method('getAllTasks')->willReturn([]);

		$result = $this->controller->listTasks();
		$data = $result->getData();

		$this->assertIsArray($data);
		$this->assertEmpty($data);
	}

	public function testCreateTask(): void {
		$dueDate = new DateTime('2025-12-31');
		
		// Mock the task service to return a task
		$mockTask = $this->createMock(\OCA\EisenhowerBoard\Entity\Task::class);
		$mockTask->method('toArray')->willReturn([
			'id' => 1,
			'user_id' => 'test-user',
			'description' => 'Test task',
			'importance' => 50,
			'due_date' => '2025-12-31T00:00:00+00:00',
			'created_at' => (new DateTime())->format(DateTime::ATOM),
			'updated_at' => (new DateTime())->format(DateTime::ATOM),
		]);

		$this->taskService->method('createTask')
			->with('test-user', 'Test task', 50, $dueDate)
			->willReturn($mockTask);

		$result = $this->controller->createTask('Test task', 50, '2025-12-31T00:00:00+00:00');
		$data = $result->getData();

		$this->assertSame(201, $result->getStatus());
		$this->assertArrayHasKey('id', $data);
		$this->assertSame(1, $data['id']);
	}

	public function testCreateTaskInvalidDueDate(): void {
		$result = $this->controller->createTask('Test task', 50, 'invalid-date');
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('error', $data);
	}

	public function testGetTaskNotFound(): void {
		$this->taskService->method('getTask')->willReturn(null);

		$result = $this->controller->getTask(999);
		$data = $result->getData();

		$this->assertSame(404, $result->getStatus());
		$this->assertArrayHasKey('error', $data);
	}

	public function testDeleteTaskNotFound(): void {
		$this->taskService->method('deleteTask')->willReturn(false);

		$result = $this->controller->deleteTask(999);
		$data = $result->getData();

		$this->assertSame(404, $result->getStatus());
		$this->assertArrayHasKey('error', $data);
	}

	public function testDeleteTaskSuccess(): void {
		$this->taskService->method('deleteTask')->willReturn(true);

		$result = $this->controller->deleteTask(1);
		$data = $result->getData();

		$this->assertSame(200, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame('Task deleted', $data['message']);
	}
}
