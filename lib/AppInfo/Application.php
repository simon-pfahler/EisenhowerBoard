<?php

declare(strict_types=1);

namespace OCA\EisenhowerBoard\AppInfo;

use OCA\EisenhowerBoard\Db\TaskMapper;
use OCA\EisenhowerBoard\Migration\Version20250101000000;
use OCA\EisenhowerBoard\Service\TaskService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IUserSession;
use OCP\IDBConnection;
use OCP\IL10N;

class Application extends App implements IBootstrap {
	public const APP_ID = 'eisenhowerboard';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register mapper - provides database operations for tasks
		$context->registerService(TaskMapper::class, function ($container) {
			return new TaskMapper(
				$container->get(IDBConnection::class)
			);
		});

		// Register service - provides business logic
		$context->registerService(TaskService::class, function ($container) {
			return new TaskService(
				$container->get(TaskMapper::class),
				$container->get(IL10N::class)
			);
		});

		// Register migration for database setup (Nextcloud 27+)
		if (method_exists($context, 'registerMigration')) {
			$context->registerMigration(\OCA\EisenhowerBoard\Migration\Version20250101000000::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}
