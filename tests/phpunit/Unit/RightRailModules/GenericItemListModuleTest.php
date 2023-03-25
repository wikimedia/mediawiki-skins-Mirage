<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\RightRailModules;

use MediaWiki\Skins\Mirage\RightRailModules\GenericItemListModule;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

class GenericItemListModuleTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\RightRailModules\GenericItemListModule::getRole
	 */
	public function testGetRole(): void {
		$module = new GenericItemListModule(
			$this->createMock( SkinMirage::class ),
			'test',
			[]
		);

		static::assertEquals(
			'navigation',
			TestingAccessWrapper::newFromObject( $module )->getRole()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\RightRailModules\GenericItemListModule::canBeShown
	 *
	 * @dataProvider provideItems
	 *
	 * @param array $items
	 */
	public function testCanBeShown( array $items ): void {
		$module = new GenericItemListModule(
			$this->createMock( SkinMirage::class ),
			'test',
			$items
		);

		static::assertEquals( $items !== [], $module->canBeShown() );
	}

	/**
	 * Data provider for testCanBeShown.
	 *
	 * @return array
	 */
	public static function provideItems(): array {
		return [
			'Empty' => [ [] ],
			'With items' => [
				[
					'item 1' => [],
					'item 2' => []
				]
			],
		];
	}
}
