<?php

namespace MediaWiki\Skins\Mirage\Tests\Structure;

/**
 * @coversNothing
 */
class BundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTest {
	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return __DIR__ . '/../../../bundlesize.config.json';
	}
}
