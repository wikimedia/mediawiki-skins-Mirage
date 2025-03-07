<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Hook;

use File;
use ImagePage;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\Hook\Handler;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\StaticUserOptionsLookup;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\User\UserOptionsLookup;
use MediaWiki\Utils\UrlUtils;
use MediaWikiUnitTestCase;
use SkinFallback;

class HandlerTest extends MediaWikiUnitTestCase {
	/**
	 * @param array $coreConfig
	 * @param array $mirageConfig
	 * @return ConfigFactory
	 */
	private function getConfigFactoryForHandler( array $coreConfig = [], array $mirageConfig = [] ): ConfigFactory {
		$configFactory = new ConfigFactory();
		$configFactory->register( 'Mirage', new HashConfig( $mirageConfig ) );
		$configFactory->register( 'main', new HashConfig( $coreConfig + [
			'UseInstantCommons' => false
		] ) );
		return $configFactory;
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onBeforePageDisplay
	 */
	public function testOnBeforePageDisplay(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( MirageWordmarkLookup::class )
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
	public function testOnBeforePageDisplayWithAvatarLookup(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( MirageWordmarkLookup::class )
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
	public function testOnBeforePageDisplayWithAvatarLookupAndWrongSkin(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( MirageWordmarkLookup::class )
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
	public function testOnResourceLoaderRegisterModules(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( MirageWordmarkLookup::class )
		);

		$resourceLoader = $this->createMock( ResourceLoader::class );
		$resourceLoader->expects( static::never() )->method( 'register' );

		$handler->onResourceLoaderRegisterModules( $resourceLoader );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onResourceLoaderRegisterModules
	 */
	public function testOnResourceLoaderRegisterModulesWithAvatarService(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			$this->createMock( AvatarLookup::class ),
			$this->createMock( MirageWordmarkLookup::class )
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
	public function testOnOutputPageBodyAttributes( ?int $option, string $expected ): void {
		$options = $option === null ? [] : [
			'Testuser' => [ 'mirage-max-width' => $option ]
		];

		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			new StaticUserOptionsLookup(
				$options,
				[ 'mirage-max-width' => Handler::MIRAGE_PARTIAL_MAX_WIDTH ]
			),
			new NullAvatarLookup(),
			$this->createMock( MirageWordmarkLookup::class )
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
	public static function provideOptions(): array {
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
	public function testOnOutputPageBodyAttributesWithOtherSkin(): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler(),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( MirageWordmarkLookup::class )
		);

		$bodyAttrs = [];

		$handler->onOutputPageBodyAttributes(
			$this->createMock( OutputPage::class ),
			$this->createMock( SkinFallback::class ),
			$bodyAttrs
		);

		static::assertEquals( [], $bodyAttrs );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onMirageGetExtraIcons
	 * @dataProvider provideIconsOptions
	 */
	public function testOnMirageGetExtraIcons( bool $useInstantCommons, array $expected ): void {
		$handler = new Handler(
			$this->getConfigFactoryForHandler( [
				'UseInstantCommons' => $useInstantCommons
			] ),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$this->createMock( MirageWordmarkLookup::class )
		);

		$icons = [];
		$handler->onMirageGetExtraIcons( $icons );

		// WikiLove might be loaded.
		unset( $icons['heart'] );

		static::assertSame(
			$expected,
			$icons
		);
	}

	/**
	 * Data provider for testOnMirageGetExtraIcons.
	 *
	 * @return array[]
	 */
	public static function provideIconsOptions(): array {
		return [
			'Without InstantCommons' => [
				false,
				[]
			],
			'With InstantCommons' => [
				true,
				[ 'logoWikimediaCommons' => [] ]
			]
		];
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onImagePageAfterImageLinks
	 */
	public function testOnImagePageAfterImageLinksWithWordmarkDisabled(): void {
		$wordmarkLookup = $this->createMock( MirageWordmarkLookup::class );
		$wordmarkLookup->method( 'getWordmarkFile' )->willReturn( null );

		$handler = new Handler(
			$this->getConfigFactoryForHandler( [], [
				'MirageEnableImageWordmark' => false
			] ),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$wordmarkLookup
		);

		$html = '';

		$handler->onImagePageAfterImageLinks( $this->createMock( ImagePage::class ), $html );

		static::assertSame( '', $html );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onImagePageAfterImageLinks
	 */
	public function testOnImagePageAfterImageLinksWithWordmarkEnabledWrongPage(): void {
		$titleMock = $this->createMock( Title::class );
		$titleMock->method( 'equals' )->willReturn( false );

		$file = $this->createMock( File::class );
		$file->method( 'getTitle' )->willReturn( $titleMock );

		$wordmarkLookup = $this->createMock( MirageWordmarkLookup::class );
		$wordmarkLookup->method( 'getWordmarkFile' )->willReturn( $file );

		$handler = new Handler(
			$this->getConfigFactoryForHandler( [], [
				'MirageEnableImageWordmark' => true
			] ),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$wordmarkLookup
		);

		$imagePage = $this->createMock( ImagePage::class );
		$imagePage->method( 'getTitle' )->willReturn( $titleMock );

		$html = '';

		$handler->onImagePageAfterImageLinks( $imagePage, $html );

		static::assertSame( '', $html );
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\Hook\Handler::onImagePageAfterImageLinks
	 */
	public function testOnImagePageAfterImageLinksWithWordmarkEnabled(): void {
		$titleMock = $this->createMock( Title::class );
		$titleMock->method( 'equals' )->willReturn( true );

		$file = $this->createMock( File::class );
		$file->method( 'getTitle' )->willReturn( $titleMock );

		$wordmarkLookup = $this->createMock( MirageWordmarkLookup::class );
		$wordmarkLookup->method( 'getWordmarkFile' )->willReturn( $file );

		$handler = new Handler(
			$this->getConfigFactoryForHandler( [], [
				'MirageEnableImageWordmark' => true
			] ),
			$this->createMock( ExtensionRegistry::class ),
			$this->createMock( TitleFactory::class ),
			$this->createMock( UrlUtils::class ),
			$this->createMock( UserOptionsLookup::class ),
			new NullAvatarLookup(),
			$wordmarkLookup
		);

		$mockContext = $this->createMock( IContextSource::class );
		$mockContext->method( 'msg' )->willReturnCallback( function ( $key, ...$param ) {
			return $this->getMockMessage( $key, $param );
		} );

		$imagePage = $this->createMock( ImagePage::class );
		$imagePage->method( 'getTitle' )->willReturn( $titleMock );
		$imagePage->method( 'getContext' )->willReturn( $mockContext );

		$html = '';

		$handler->onImagePageAfterImageLinks( $imagePage, $html );

		static::assertSame(
			Html::warningBox( 'mirage-wordmark-file-warning' ),
			$html
		);
	}
}
