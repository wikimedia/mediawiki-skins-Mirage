<?php

namespace MediaWiki\Skins\Mirage\Tests\Structure;

use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWiki\Tests\Structure\BundleSizeTestBase;

/**
 * @coversNothing
 */
class BundleSizeTest extends BundleSizeTestBase {
	/**
	 * @before
	 *
	 * Mock MirageWordmarkLookup to prevent it from doing lookups.
	 */
	protected function replaceWordmarkLookupWithMockSetUp(): void {
		$this->setService( 'Mirage.WordmarkLookup', $this->createMock( MirageWordmarkLookup::class ) );
	}

	/** @inheritDoc */
	public static function getBundleSizeConfigData(): string {
		return __DIR__ . '/../../../bundlesize.config.json';
	}
}
