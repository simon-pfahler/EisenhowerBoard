<?php

declare(strict_types=1);

// Mock all required Nextcloud classes and interfaces using eval for namespaces

// Mock OCP namespace interfaces
if (!interface_exists('OCP\IRequest')) {
	eval('namespace OCP { interface IRequest { public function getUserId(): string; } }');
}
if (!interface_exists('OCP\IL10N')) {
	eval('namespace OCP { interface IL10N { public function t(string $text): string; } }');
}
if (!interface_exists('OCP\IDBConnection')) {
	eval('namespace OCP { interface IDBConnection { public function getQueryBuilder(); public function getDatabaseConnection(); } }');
}
if (!interface_exists('OCP\IServerContainer')) {
	eval('namespace OCP { interface IServerContainer { public function getDatabaseConnection(); } }');
}
if (!interface_exists('OCP\DB\QueryBuilder\IQueryBuilder')) {
	eval('namespace OCP\DB\QueryBuilder { interface IQueryBuilder { public const PARAM_DATE = 2; } }');
}

// Mock OCP AppFramework interfaces
if (!interface_exists('OCP\AppFramework\Bootstrap\IBootstrap')) {
	eval('namespace OCP\AppFramework\Bootstrap { interface IBootstrap {} }');
}
if (!interface_exists('OCP\AppFramework\Bootstrap\IBootContext')) {
	eval('namespace OCP\AppFramework\Bootstrap { interface IBootContext {} }');
}
if (!interface_exists('OCP\AppFramework\Bootstrap\IRegistrationContext')) {
	eval('namespace OCP\AppFramework\Bootstrap { interface IRegistrationContext { public function registerService(string $class, callable $factory): void; public function registerMigration(string $class): void; } }');
}

// Mock Doctrine classes
if (!class_exists('\\Doctrine\\DBAL\\Result')) {
	eval('namespace Doctrine\DBAL { class Result { public function fetchAssociative(): array|false { return false; } public function fetchAllAssociative(): array { return []; } public function rowCount(): int { return 0; } } }');
}
if (!class_exists('\\Doctrine\\DBAL\\Driver\\Connection')) {
	eval('namespace Doctrine\DBAL\Driver { class Connection {} }');
}

// Mock OC classes
if (!class_exists('OC_App')) {
	class OC_App {
		public static function loadApp(string $appId): void {}
	}
}

if (!class_exists('OC_Hook')) {
	class OC_Hook {
		public static function clear(): void {}
	}
}

// Create concrete implementations for testing
if (!class_exists('OCP_Request')) {
	class OCP_Request implements OCP\IRequest {
		public function getUserId(): string {
			return 'test-user';
		}
	}
}

if (!class_exists('OCP_L10N')) {
	class OCP_L10N implements OCP\IL10N {
		public function t(string $text): string {
			return $text;
		}
	}
}

if (!class_exists('OCP_DB_QueryBuilder_Expr')) {
	class OCP_DB_QueryBuilder_Expr {
		public function eq($a, $b) { return 'eq'; }
		public function andWhere($condition) { return 'andWhere'; }
	}
}

if (!class_exists('OCP_DB_Connection')) {
	class OCP_DB_Connection implements OCP\IDBConnection {
		public function getQueryBuilder() {
			return new OCP_DB_QueryBuilder_IQueryBuilder();
		}
		public function getDatabaseConnection() {
			return $this;
		}
	}
}

if (!class_exists('OCP_DB_QueryBuilder_IQueryBuilder')) {
	class OCP_DB_QueryBuilder_IQueryBuilder implements OCP\DB\QueryBuilder\IQueryBuilder {
		public function select() { return $this; }
		public function from() { return $this; }
		public function where() { return $this; }
		public function andWhere() { return $this; }
		public function orderBy() { return $this; }
		public function values() { return $this; }
		public function insert() { return $this; }
		public function update() { return $this; }
		public function set() { return $this; }
		public function delete() { return $this; }
		public function executeQuery() { 
			return new class { 
				public function fetchAssociative() { return false; } 
				public function fetchAllAssociative(): array { return []; } 
				public function rowCount(): int { return 0; } 
			};
		}
		public function executeStatement(): int { return 1; }
		public function createNamedParameter($value, $type = null) { return $value; }
		public function createFunction() { return ''; }
		public function expr() { return new OCP_DB_QueryBuilder_Expr(); }
		public function getLastInsertId() { return '1'; }
		public const PARAM_DATE = 2;
	}
}

if (!class_exists('OCP_IServerContainer')) {
	class OCP_IServerContainer implements OCP\IServerContainer {
		public function getDatabaseConnection() {
			return new OCP_DB_Connection();
		}
	}
}

if (!class_exists('OCP_App')) {
	class OCP_App {
		public const APP_ID = '';
		public function __construct(string $appName) {}
	}
	class_alias('OCP_App', 'OCP\AppFramework\App');
}

// Mock OCSController
if (!class_exists('OCP\AppFramework\OCSController')) {
	eval('namespace OCP\AppFramework { class OCSController { public function __construct(string $appName, \OCP\IRequest $request) {} } }');
}

// Mock attribute classes
if (!class_exists('OCP_AppFramework_Http_Attribute_FrontpageRoute')) {
	class OCP_AppFramework_Http_Attribute_FrontpageRoute {}
	class_alias('OCP_AppFramework_Http_Attribute_FrontpageRoute', 'OCP\AppFramework\Http\Attribute\FrontpageRoute');
}
if (!class_exists('OCP_AppFramework_Http_Attribute_NoAdminRequired')) {
	class OCP_AppFramework_Http_Attribute_NoAdminRequired {}
	class_alias('OCP_AppFramework_Http_Attribute_NoAdminRequired', 'OCP\AppFramework\Http\Attribute\NoAdminRequired');
}
if (!class_exists('OCP_AppFramework_Http_Attribute_NoCSRFRequired')) {
	class OCP_AppFramework_Http_Attribute_NoCSRFRequired {}
	class_alias('OCP_AppFramework_Http_Attribute_NoCSRFRequired', 'OCP\AppFramework\Http\Attribute\NoCSRFRequired');
}
if (!class_exists('OCP_AppFramework_Http_Attribute_OpenAPI')) {
	class OCP_AppFramework_Http_Attribute_OpenAPI {}
	class_alias('OCP_AppFramework_Http_Attribute_OpenAPI', 'OCP\AppFramework\Http\Attribute\OpenAPI');
}
if (!class_exists('OCP_AppFramework_Http_Attribute_ApiRoute')) {
	class OCP_AppFramework_Http_Attribute_ApiRoute {
		public function __construct(string $verb, string $url) {}
	}
	class_alias('OCP_AppFramework_Http_Attribute_ApiRoute', 'OCP\AppFramework\Http\Attribute\ApiRoute');
}

if (!class_exists('OCP_AppFramework_Bootstrap_IRegistrationContext')) {
	class OCP_AppFramework_Bootstrap_IRegistrationContext implements OCP\AppFramework\Bootstrap\IRegistrationContext {
		public function registerService(string $class, callable $factory): void {}
		public function registerMigration(string $class): void {}
	}
	class_alias('OCP_AppFramework_Bootstrap_IRegistrationContext', 'OCP\AppFramework\Bootstrap\IRegistrationContext');
}

// Load the autoloader for our app
require_once __DIR__ . '/../vendor/autoload.php';
