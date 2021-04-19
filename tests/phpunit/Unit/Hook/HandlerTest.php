<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Hook;

use ConfigFactory;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\Hook\Handler;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\User\StaticUserOptionsLookup;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\User\UserOptionsLookup;
use MediaWikiUnitTestCase;
use OutputPage;
use ResourceLoader;
use SkinFallback;
use TitleFactory;

class HandlerTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onBeforePageDisplay
	 */
	public function testOnBeforePageDisplay() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( ConfigFactory::class )
		);

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( static::never() )->method( 'addModuleStyles' );

		$handler->onBeforePageDisplay(
			$outputPage,
			$this->createMock( SkinMirage::class )
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onBeforePageDisplay
	 */
	public function testOnBeforePageDisplayWithAvatarLookup() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( ConfigFactory::class )
		);

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage
			->expects( static::once() )
			->method( 'addModuleStyles' )
			->with( 'skins.mirage.avatars.styles' );

		$handler->onBeforePageDisplay(
			$outputPage,
			$this->createMock( SkinMirage::class )
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onBeforePageDisplay
	 */
	public function testOnBeforePageDisplayWithAvatarLookupAndWrongSkin() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( ConfigFactory::class )
		);

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( static::never() )->method( 'addModuleStyles' );

		$handler->onBeforePageDisplay(
			$outputPage,
			$this->createMock( SkinFallback::class )
		);
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onResourceLoaderRegisterModules
	 */
	public function testOnResourceLoaderRegisterModules() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( ConfigFactory::class )
		);

		$resourceLoader = $this->createMock( ResourceLoader::class );
		$resourceLoader->expects( static::never() )->method( 'register' );

		$handler->onResourceLoaderRegisterModules( $resourceLoader );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onResourceLoaderRegisterModules
	 */
	public function testOnResourceLoaderRegisterModulesWithAvatarService() : void {
		$handler = new Handler(
			$this->createMock( TitleFactory::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( ConfigFactory::class )
		);

		$resourceLoader = $this->createMock( ResourceLoader::class );
		$resourceLoader->expects( static::once() )->method( 'register' );

		$handler->onResourceLoaderRegisterModules( $resourceLoader );
	}

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
			new NullAvatarLookup(),
			$this->createMock( ConfigFactory::class )
		);

		$skin = $this->createMock( SkinMirage::class );
		$skin->method( 'getUser' )->willReturn( new UserIdentityValue(
			1,
			'Testuser'
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
			new NullAvatarLookup(),
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
