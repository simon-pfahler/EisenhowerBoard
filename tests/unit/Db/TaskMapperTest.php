<?php

declare(strict_types=1);

namespace Unit\Db;

use DateTime;
use OCA\EisenhowerBoard\Db\TaskMapper;
use OCA\EisenhowerBoard\Entity\Task;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;

final class TaskMapperTest extends TestCase {
	private TaskMapper $mapper;
	private IDBConnection $db;

	protected function setUp(): void {
		$this->db = $this->createMock(IDBConnection::class);
		$this->mapper = new TaskMapper($this->db);
	}

	public function testFindAllByUser(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		// Configure the query builder mock
		$qb->method('select')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('orderBy')->willReturnSelf();
		$qb->method('executeQuery')->willReturn(
			$this->createMockResultSet([
				['id' => 1, 'user_id' => 'user1', 'description' => 'Task 1', 'importance' => 50, 
				 'due_date' => '2025-12-31 12:00:00', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-02 00:00:00'],
				['id' => 2, 'user_id' => 'user1', 'description' => 'Task 2', 'importance' => 75,
				 'due_date' => '2026-01-01 12:00:00', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-02 00:00:00'],
			])
		);

		// Configure the database mock to return the query builder
		$this->db->method('getQueryBuilder')->willReturn($qb);

		$result = $this->mapper->findAllByUser('user1');

		$this->assertCount(2, $result);
		$this->assertInstanceOf(Task::class, $result[0]);
		$this->assertSame('Task 1', $result[0]->getDescription());
		$this->assertSame(50, $result[0]->getImportance());
	}

	public function testFindById(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('select')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('andWhere')->willReturnSelf();
		$qb->method('executeQuery')->willReturn(
			$this->createMockResultSet([
				['id' => 1, 'user_id' => 'user1', 'description' => 'Task 1', 'importance' => 50,
				 'due_date' => '2025-12-31 12:00:00', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-02 00:00:00']
			])
		);

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$result = $this->mapper->find(1, 'user1');

		$this->assertInstanceOf(Task::class, $result);
		$this->assertSame(1, $result->getId());
		$this->assertSame('user1', $result->getUserId());
	}

	public function testFindByIdNotFound(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('select')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('executeQuery')->willReturn(
			$this->createMockResultSet([])
		);

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$result = $this->mapper->find(999, 'user1');

		$this->assertNull($result);
	}

	public function testInsert(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('insert')->willReturnSelf();
		$qb->method('values')->willReturnSelf();
		$qb->method('executeStatement')->willReturn(1);
		$qb->method('getLastInsertId')->willReturn('1');

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$task = new Task(null, 'user1', 'New task', 50, new DateTime('+1 day'));
		$result = $this->mapper->insert($task);

		$this->assertInstanceOf(Task::class, $result);
		$this->assertSame(1, $result->getId());
		$this->assertSame('New task', $result->getDescription());
	}

	public function testUpdate(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('update')->willReturnSelf();
		$qb->method('set')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('executeStatement')->willReturn(1);

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$task = new Task(1, 'user1', 'Updated task', 75, new DateTime('+2 days'));
		$result = $this->mapper->update($task);

		$this->assertSame($task, $result);
	}

	public function testDelete(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('delete')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('andWhere')->willReturnSelf();
		$qb->method('executeStatement')->willReturn(1);

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$result = $this->mapper->delete(1, 'user1');

		$this->assertTrue($result);
	}

	public function testDeleteNotFound(): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('delete')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('andWhere')->willReturnSelf();
		$qb->method('executeStatement')->willReturn(0);

		$this->db->method('getQueryBuilder')->willReturn($qb);

		$result = $this->mapper->delete(999, 'user1');

		$this->assertFalse($result);
	}

	/**
	 * Create a mock result set from an array of rows.
	 *
	 * @param array $rows Array of associative arrays representing database rows
	 * @return \PHPUnit\Framework\MockObject\MockObject Mock result object
	 */
	private function createMockResultSet(array $rows): object {
		$result = $this->createMock(\Doctrine\DBAL\Result::class);
		
		$result->method('fetchAssociative')->willReturnOnConsecutiveCalls(
			...$rows,
			false // Return false after all rows are fetched
		);

		return $result;
	}
}
