<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Hook;

use Generator;
use MediaWiki\Skins\Mirage\Hook\HookRunner;

// HookRunnerTest.php isn't stable to extend and therefor isn't included in the test autoloader,
// so load this manually.
require_once __DIR__ . '/../../../../../../tests/phpunit/unit/includes/HookContainer/HookRunnerTest.php';

/**
 * @covers \MediaWiki\Skins\Mirage\Hook\HookRunner
 */
class HookRunnerTest extends \MediaWiki\Tests\HookContainer\HookRunnerTest {
	/**
	 * @inheritDoc
	 */
	public function provideHookRunners() : Generator {
		yield HookRunner::class => [ HookRunner::class ];
	}
}
