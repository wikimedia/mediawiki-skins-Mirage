<?php

namespace MediaWiki\Skins\Mirage\Tests\Integration\ResourceLoader;

use File;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\MultiConfig;
use MediaWiki\MainConfigNames;
use MediaWiki\ResourceLoader\SkinModule;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWiki\Skins\Mirage\ResourceLoader\MirageResourceLoaderModule;
use ResourceLoaderTestCase;
use Wikimedia\Minify\CSSMin;
use function array_pop;
use function sha1;

/**
 * @covers \MediaWiki\Skins\Mirage\ResourceLoader\MirageResourceLoaderModule
 */
class MirageResourceLoaderModuleTest extends ResourceLoaderTestCase {
	private const WORDMARK_URL = '/img.png';

	private function setWordmarkLookup( bool $wordmarkDefined ): void {
		$fileMock = $this->createMock( File::class );
		$fileMock->method( 'getSha1' )->willReturn( sha1( 'test' ) );

		$mock = $this->createMock( MirageWordmarkLookup::class );
		$mock->method( 'getWordmarkUrl' )->willReturn(
			$wordmarkDefined ? self::WORDMARK_URL : null
		);
		$mock->method( 'getWordmarkFile' )->willReturn(
			$wordmarkDefined ? $fileMock : null
		);

		$this->setService(
			'Mirage.WordmarkLookup',
			$mock
		);
	}

	public function testGetDefinitionSummaryWithoutWordmark(): void {
		$this->setWordmarkLookup( false );

		$context = $this->getResourceLoaderContext();
		$config = $context->getResourceLoader()->getConfig();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $config );
		$parentModule = new SkinModule();
		$parentModule->setConfig( $config );

		$parentSummary = $parentModule->getDefinitionSummary( $context );
		$moduleSummary = $module->getDefinitionSummary( $context );

		// The _class property differs between parent and child.
		unset( $parentSummary['_class'], $moduleSummary['_class'] );

		static::assertEquals(
			$parentSummary,
			$moduleSummary
		);
	}

	public function testGetDefinitionSummaryWithWordmark(): void {
		$this->setWordmarkLookup( true );

		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $context->getResourceLoader()->getConfig() );
		$summary = $module->getDefinitionSummary( $context );

		$item = array_pop( $summary );

		static::assertEquals( [ 'mirage-wordmark' => sha1( 'test' ) ], $item );
	}

	public function testGetPreloadLinksWithoutWordmark(): void {
		$this->setWordmarkLookup( false );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $context->getResourceLoader()->getConfig() );
		$preloadLinks = $module->getPreloadLinks( $context );

		static::assertArrayNotHasKey( self::WORDMARK_URL, $preloadLinks );
	}

	public function testGetPreloadLinksWithWordmark(): void {
		$this->setWordmarkLookup( true );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $context->getResourceLoader()->getConfig() );
		$preloadLinks = $module->getPreloadLinks( $context );

		static::assertArrayHasKey( self::WORDMARK_URL, $preloadLinks );
		static::assertEquals( [ 'as' => 'image' ], $preloadLinks[self::WORDMARK_URL] );
	}

	public function testGetPreloadLinksIconFallback(): void {
		$this->setWordmarkLookup( false );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( new MultiConfig( [
			new HashConfig( [
				MainConfigNames::Logos => [
					'1x' => $GLOBALS['wgLogo'],
					'svg' => '/img.svg'
				],
				// Trick OutputPage::transformResourcePath into thinking this is something that
				// cannot be transformed.
				MainConfigNames::ResourceBasePath => '//'
			] ),
			$context->getResourceLoader()->getConfig()
		] ) );
		$preloadLinks = $module->getPreloadLinks( $context );

		static::assertArrayNotHasKey( self::WORDMARK_URL, $preloadLinks );
		static::assertArrayHasKey( '/img.svg', $preloadLinks );
	}

	public function testGetPreloadLinksWithWordmarkAndTagline(): void {
		$this->setWordmarkLookup( false );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( new MultiConfig( [
			new HashConfig( [
				MainConfigNames::Logos => [
					'1x' => '/1x.png',
					'wordmark' => [
						'src' => '/wordmark.png',
						'width' => 32,
						'height' => 32,
					],
					'tagline' => [
						'src' => '/tagline.png',
						'width' => 32,
						'height' => 32,
					],
				],
				// Trick OutputPage::transformResourcePath into thinking this is something that
				// cannot be transformed.
				MainConfigNames::ResourceBasePath => '//'
			] ),
			$context->getResourceLoader()->getConfig()
		] ) );
		$preloadLinks = $module->getPreloadLinks( $context );

		static::assertArrayHasKey( '/wordmark.png', $preloadLinks );
		static::assertArrayHasKey( '/tagline.png', $preloadLinks );
	}

	public function testGetPreloadLinksWith1xWordmark(): void {
		$this->setWordmarkLookup( false );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( new MultiConfig( [
			new HashConfig( [
				MainConfigNames::Logos => [
					'1x' => '/1x.png',
					'wordmark' => [
						'src' => '/wordmark.png',
						'1x' => '/wordmark.svg',
						'width' => 32,
						'height' => 32,
					],
				],
				// Trick OutputPage::transformResourcePath into thinking this is something that
				// cannot be transformed.
				MainConfigNames::ResourceBasePath => '//'
			] ),
			$context->getResourceLoader()->getConfig()
		] ) );
		$preloadLinks = $module->getPreloadLinks( $context );

		static::assertArrayHasKey( '/wordmark.svg', $preloadLinks );
	}

	public function testGetStylesWithoutWordmark(): void {
		$this->setWordmarkLookup( false );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $context->getResourceLoader()->getConfig() );
		$styles = $module->getStyles( $context );
		$wordmark = CSSMin::buildUrlValue( self::WORDMARK_URL );

		static::assertNotContains(
			"#mirage-wordmark { background-image: $wordmark; }",
			$styles['all'] ?? []
		);
	}

	public function testGetStylesWithWordmark(): void {
		$this->setWordmarkLookup( true );
		$context = $this->getResourceLoaderContext();

		$module = new MirageResourceLoaderModule();
		$module->setConfig( $context->getResourceLoader()->getConfig() );
		$styles = $module->getStyles( $context );
		$wordmark = CSSMin::buildUrlValue( self::WORDMARK_URL );

		static::assertContains(
			"#mirage-wordmark { background-image: $wordmark; }",
			$styles['all']
		);
	}
}
