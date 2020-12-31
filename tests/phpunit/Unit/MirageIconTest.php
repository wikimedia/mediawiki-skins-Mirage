<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use HtmlArmor;
use MediaWiki\Skins\Mirage\MirageIcon;
use MediaWikiUnitTestCase;

class MirageIconTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::small
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testSmall() : void {
		$icon = MirageIcon::small( 'test' );

		static::assertStringContainsString(
			'icon-small',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::medium
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testMedium() : void {
		$icon = MirageIcon::medium( 'test' );

		static::assertStringContainsString(
			'icon-medium',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setVariant
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testSetVariant() : void {
		$icon = MirageIcon::medium( 'test' )
			->setVariant( 'invert' );

		static::assertStringContainsString(
			'icon-test-invert',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::hideLabel
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testHideLabel() : void {
		$icon = MirageIcon::medium( 'test' )
			->hideLabel();

		static::assertStringContainsString(
			'icon-no-label',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::hideLabel
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testHideLabelWithFalse() : void {
		$icon = MirageIcon::medium( 'test' )
			->hideLabel( false );

		static::assertStringNotContainsString(
			'icon-no-label',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setClasses
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testAddClasses() : void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setClasses( 'test-class' );

		static::assertEquals( <<<HTML
<span class="test-class mirage-ooui-icon mirage-ooui-icon-placeholder mirage-ooui-icon-medium"></span>
HTML
			,
			(string)$icon
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setContent
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testSetContent() : void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setContent( new HtmlArmor( '<script></script>' ) );

		static::assertEquals( <<<HTML
<span class="mirage-ooui-icon mirage-ooui-icon-placeholder mirage-ooui-icon-medium"><script></script></span>
HTML
			,
			(string)$icon
		);
	}
}
