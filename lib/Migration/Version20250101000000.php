<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create the tasks table for the Eisenhower Board app.
 */
class Version20250101000000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('eisenhowerboard_tasks')) {
			$table = $schema->createTable('eisenhowerboard_tasks');

			$table->addAutoincrementColumn('id', 'bigint', true);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('description', 'text', [
				'notnull' => true,
			]);
			$table->addColumn('importance', 'integer', [
				'notnull' => true,
				'unsigned' => true,
				'default' => 50,
			]);
			$table->addColumn('due_date', 'datetime', [
				'notnull' => true,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
				'default' => '(now())',
			]);
			$table->addColumn('updated_at', 'datetime', [
				'notnull' => true,
				'default' => '(now())',
			]);

			$table->addPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'eisenhowerboard_tasks_user_id_idx');
			$table->addIndex(['due_date'], 'eisenhowerboard_tasks_due_date_idx');

			$output->info('Created table eisenhowerboard_tasks');
		} else {
			$output->info('Table eisenhowerboard_tasks already exists');
		}

		return $schema;
	}
}
