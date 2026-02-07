<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use MediaWiki\Skins\Mirage\MirageIcon;
use MediaWikiUnitTestCase;
use Wikimedia\HtmlArmor\HtmlArmor;

class MirageIconTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::small
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 */
	public function testSmall(): void {
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
	public function testMedium(): void {
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
	public function testSetVariant(): void {
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
	public function testHideLabel(): void {
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
	public function testHideLabelWithFalse(): void {
		$icon = MirageIcon::medium( 'test' )
			->hideLabel( false );

		static::assertStringNotContainsString(
			'icon-no-label',
			$icon->toClasses()
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setClasses
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::toClasses
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testAddClasses(): void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setClasses( 'test-class' );

		$expectedClasses = 'test-class skin-mirage-ooui-icon skin-mirage-ooui-icon-placeholder ' .
						   'skin-mirage-ooui-icon-medium';

		static::assertEquals( $expectedClasses, $icon->toClasses() );
		static::assertEquals( "<span class=\"$expectedClasses\"></span>", (string)$icon );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setContent
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testSetContent(): void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setContent( new HtmlArmor( '<script></script>' ) );

		$classes = 'skin-mirage-ooui-icon skin-mirage-ooui-icon-placeholder ' .
				   'skin-mirage-ooui-icon-medium';

		static::assertEquals( "<span class=\"$classes\"><script></script></span>", (string)$icon );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setElement
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testSetElement(): void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setElement( 'div' );

		$classes = 'skin-mirage-ooui-icon skin-mirage-ooui-icon-placeholder ' .
				   'skin-mirage-ooui-icon-medium';

		static::assertEquals( "<div class=\"$classes\"></div>", (string)$icon );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::setAttributes
	 * @covers \MediaWiki\Skins\Mirage\MirageIcon::__toString
	 */
	public function testSetAttributes(): void {
		$icon = MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
			->setAttributes( [
				'class' => 'ignored',
				'for' => 'a-test'
			] );

		$classes = 'skin-mirage-ooui-icon skin-mirage-ooui-icon-placeholder ' .
				   'skin-mirage-ooui-icon-medium';

		static::assertEquals( "<span class=\"$classes\" for=\"a-test\"></span>", (string)$icon );
	}
}
