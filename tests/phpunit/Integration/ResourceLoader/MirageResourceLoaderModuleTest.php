<?php

namespace MediaWiki\Skins\Mirage\Tests\Integration\ResourceLoader;

use File;
use HashConfig;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWiki\Skins\Mirage\ResourceLoader\MirageResourceLoaderModule;
use MultiConfig;
use ResourceLoaderSkinModule;
use ResourceLoaderTestCase;
use Wikimedia\Minify\CSSMin;
use function array_pop;
use function sha1;

/**
 * @covers \MediaWiki\Skins\Mirage\ResourceLoader\MirageResourceLoaderModule
 */
class MirageResourceLoaderModuleTest extends ResourceLoaderTestCase {
	private const WORDMARK_URL = '/img.png';

	private function setWordmarkLookup( bool $wordmarkDefined ) : void {
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
			'MirageWordmarkLookup',
			$mock
		);
	}

	public function testGetDefinitionSummaryWithoutWordmark() : void {
		$this->setWordmarkLookup( false );

		$module = new MirageResourceLoaderModule();
		$parentModule = new ResourceLoaderSkinModule();
		$parentSummary = $parentModule->getDefinitionSummary( $this->getResourceLoaderContext() );
		$moduleSummary = $module->getDefinitionSummary( $this->getResourceLoaderContext() );

		// The _class property differs between parent and child.
		unset( $parentSummary['_class'], $moduleSummary['_class'] );

		static::assertEquals(
			$parentSummary,
			$moduleSummary
		);
	}

	public function testGetDefinitionSummaryWithWordmark() : void {
		$this->setWordmarkLookup( true );

		$module = new MirageResourceLoaderModule();
		$summary = $module->getDefinitionSummary( $this->getResourceLoaderContext() );

		$item = array_pop( $summary );

		static::assertEquals( [ 'mirage-wordmark' => sha1( 'test' ) ], $item );
	}

	public function testGetPreloadLinksWithoutWordmark() : void {
		$this->setWordmarkLookup( false );

		$module = new MirageResourceLoaderModule();
		$preloadLinks = $module->getPreloadLinks( $this->getResourceLoaderContext() );

		static::assertArrayNotHasKey( self::WORDMARK_URL, $preloadLinks );
	}

	public function testGetPreloadLinksWithWordmark() : void {
		$this->setWordmarkLookup( true );

		$module = new MirageResourceLoaderModule();
		$preloadLinks = $module->getPreloadLinks( $this->getResourceLoaderContext() );

		static::assertArrayHasKey( self::WORDMARK_URL, $preloadLinks );
		static::assertEquals( [ 'as' => 'image' ], $preloadLinks[self::WORDMARK_URL] );
	}

	public function testGetPreloadLinksIconFallback() : void {
		$this->setWordmarkLookup( false );

		$module = new MirageResourceLoaderModule();
		$module->setConfig( new MultiConfig( [
			new HashConfig( [
				'Logos' => [
					'1x' => $GLOBALS['wgLogo'],
					'svg' => '/img.svg'
				],
				// Trick OutputPage::transformResourcePath into thinking this is something that
				// cannot be transformed.
				'ResourceBasePath' => '//'
			] ),
			$module->getConfig()
		] ) );
		$preloadLinks = $module->getPreloadLinks( $this->getResourceLoaderContext() );

		static::assertArrayNotHasKey( self::WORDMARK_URL, $preloadLinks );
		static::assertArrayHasKey( '/img.svg', $preloadLinks );
	}

	public function testGetStylesWithoutWordmark() : void {
		$this->setWordmarkLookup( false );

		$module = new MirageResourceLoaderModule();
		$styles = $module->getStyles( $this->getResourceLoaderContext() );
		$wordmark = CSSMin::buildUrlValue( self::WORDMARK_URL );

		static::assertNotContains(
			"#mirage-wordmark { background-image: $wordmark; }",
			$styles['all']
		);
	}

	public function testGetStylesWithWordmark() : void {
		$this->setWordmarkLookup( true );

		$module = new MirageResourceLoaderModule();
		$styles = $module->getStyles( $this->getResourceLoaderContext() );
		$wordmark = CSSMin::buildUrlValue( self::WORDMARK_URL );

		static::assertContains(
			"#mirage-wordmark { background-image: $wordmark; }",
			$styles['all']
		);
	}
}
