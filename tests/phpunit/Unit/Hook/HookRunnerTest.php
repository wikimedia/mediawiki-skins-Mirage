<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Hook;

use Generator;
use MediaWiki\Skins\Mirage\Hook\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Skins\Mirage\Hook\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {
	/**
	 * @inheritDoc
	 */
	public function provideHookRunners(): Generator {
		yield HookRunner::class => [ HookRunner::class ];
	}
}
