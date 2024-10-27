<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\RightRailModules;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Skins\Mirage\RightRailModules\UserToolsModule;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Skins\Mirage\RightRailModules\UserToolsModule
 */
class UserToolsModuleTest extends MediaWikiUnitTestCase {
	public function testExtraModuleInExtensionRegistryAttribute(): void {
		ExtensionRegistry::enableForTest();

		$sc = ExtensionRegistry::getInstance()->setAttributeForTest( 'MirageExtraUserTools', [
			'testmodule'
		] );

		static::assertContains( 'testmodule', UserToolsModule::getUserTools() );
	}

	public function testExtraModuleInList(): void {
		ExtensionRegistry::enableForTest();

		$sc = ExtensionRegistry::getInstance()->setAttributeForTest( 'MirageExtraUserTools', [
			'testmodule'
		] );

		$module = new UserToolsModule(
			$this->createMock( SkinMirage::class ),
			[
				'blockip' => [],
				'testmodule' => [],
				'info' => []
			]
		);

		$this->assertArrayEquals(
			[
				'blockip' => [],
				'testmodule' => []
			],
			TestingAccessWrapper::newFromObject( $module )->items
		);
	}
}
