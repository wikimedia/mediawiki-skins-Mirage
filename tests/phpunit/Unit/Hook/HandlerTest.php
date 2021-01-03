<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Hook;

use ConfigFactory;
use MediaWiki\Skins\Mirage\Hook\Handler;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\User\StaticUserOptionsLookup;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\User\UserOptionsLookup;
use MediaWikiUnitTestCase;
use OutputPage;
use SkinFallback;
use TitleFactory;

class HandlerTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onOutputPageBodyAttributes
	 *
	 * @dataProvider provideOptions
	 *
	 * @param int|null $option
	 * @param string $expected
	 */
	public function testOnOutputPageBodyAttributes( ?int $option, string $expected ) : void {
		$options = $option === null ? [] : [
			'Testuser' => [ 'mirage-max-width' => $option ]
		];

		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			new StaticUserOptionsLookup(
				$options,
				[ 'mirage-max-width' => Handler::MIRAGE_PARTIAL_MAX_WIDTH ]
			),
			$this->createMock( ConfigFactory::class )
		);

		$skin = $this->createMock( SkinMirage::class );
		$skin->method( 'getUser' )->willReturn( new UserIdentityValue(
			1,
			'Testuser',
			1
		) );

		$bodyAttrs = [ 'class' => 'testclass' ];

		$handler->onOutputPageBodyAttributes(
			$this->createMock( OutputPage::class ),
			$skin,
			$bodyAttrs
		);

		static::assertEquals(
			"testclass$expected",
			$bodyAttrs['class']
		);
	}

	/**
	 * Data provider for testOnOutputPageBodyAttributes.
	 *
	 * @return array[]
	 */
	public function provideOptions() : array {
		return [
			'No preference set' => [ null, ' skin-mirage-limit-content-width-selectively' ],
			'Invalid' => [ -1, ' skin-mirage-limit-content-width-selectively' ],
			'MIRAGE_MAX_WIDTH' => [ Handler::MIRAGE_MAX_WIDTH, ' skin-mirage-limit-content-width' ],
			'MIRAGE_PARTIAL_MAX_WIDTH' => [
				Handler::MIRAGE_PARTIAL_MAX_WIDTH,
				' skin-mirage-limit-content-width-selectively'
			],
			'MIRAGE_NO_MAX_WIDTH' => [ Handler::MIRAGE_NO_MAX_WIDTH, '' ],
		];
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onOutputPageBodyAttributes
	 */
	public function testOnOutputPageBodyAttributesWithOtherSkin() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( ConfigFactory::class )
		);

		$bodyAttrs = [];

		$handler->onOutputPageBodyAttributes(
			$this->createMock( OutputPage::class ),
			$this->createMock( SkinFallback::class ),
			$bodyAttrs
		);

		static::assertEmpty( $bodyAttrs );
	}
}
