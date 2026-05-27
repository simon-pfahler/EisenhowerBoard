<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\EisenhowerBoard\AppInfo\Application::APP_ID, OCA\EisenhowerBoard\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\EisenhowerBoard\AppInfo\Application::APP_ID, OCA\EisenhowerBoard\AppInfo\Application::APP_ID . '-main');

?>

<div id="eisenhowerboard"></div>
