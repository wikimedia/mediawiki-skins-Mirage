<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use MediaWiki\Skins\Mirage\MirageIndicator;
use MediaWikiUnitTestCase;

class MirageIndicatorTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIndicator::__construct
	 * @covers \MediaWiki\Skins\Mirage\MirageIndicator::toClasses
	 */
	public function testDefaults() : void {
		$indicator = new MirageIndicator( 'test' );

		static::assertStringStartsWith(
			'mirage-ooui-indicator',
			$indicator->toClasses()
		);
		static::assertStringContainsString(
			'no-label',
			$indicator->toClasses()
		);
		static::assertStringContainsString(
			'icon-small',
			$indicator->toClasses()
		);
	}
}
