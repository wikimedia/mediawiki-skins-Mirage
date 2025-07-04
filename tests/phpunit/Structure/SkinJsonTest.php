<?php

namespace MediaWiki\Skins\Mirage\Tests\Structure;

use MediaWiki\Tests\ExtensionJsonTestBase;

/**
 * @coversNothing
 */
class SkinJsonTest extends ExtensionJsonTestBase {
	protected static string $extensionJsonPath = __DIR__ . '/../../../skin.json';

	protected ?string $serviceNamePrefix = 'Mirage.';
}
