<?php

namespace MediaWiki\Skins\Mirage\Tests\Structure;

use MediaWiki\Skins\Mirage\MirageWordmarkLookup;

/**
 * @coversNothing
 */
class BundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTestBase {
	/**
	 * @before
	 *
	 * Mock MirageWordmarkLookup to prevent it from doing lookups.
	 */
	protected function replaceWordmarkLookupWithMockSetUp(): void {
		$this->setService( 'Mirage.WordmarkLookup', $this->createMock( MirageWordmarkLookup::class ) );
	}

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return __DIR__ . '/../../../bundlesize.config.json';
	}
}
