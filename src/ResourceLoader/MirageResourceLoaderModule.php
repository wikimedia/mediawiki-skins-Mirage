<?php

namespace MediaWiki\Skins\Mirage\ResourceLoader;

use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\Context as ResourceLoaderContext;
use MediaWiki\ResourceLoader\FilePath;
use MediaWiki\ResourceLoader\SkinModule;
use MediaWiki\Skins\Mirage\ThemeRegistry;
use Wikimedia\Minify\CSSMin;

class MirageResourceLoaderModule extends SkinModule {
	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array[]
	 */
	public function getStyles( ResourceLoaderContext $context ): array {
		$styles = parent::getStyles( $context );

		$wordmark = MediaWikiServices::getInstance()->getService( 'Mirage.WordmarkLookup' )
			->getWordmarkUrl();

		if ( $wordmark !== null ) {
			$wordmarkUrl = CSSMin::buildUrlValue( $wordmark );

			$styles['all'][] = <<<CSS
#mirage-wordmark { background-image: $wordmarkUrl; }
CSS;
		}

		return $styles;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getStyleFiles( ResourceLoaderContext $context ): array {
		$styles = parent::getStyleFiles( $context );

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Theme' ) ) {
			$themeRegistry = new ThemeRegistry(
				MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'Mirage' )
			);

			foreach ( $themeRegistry->getThemeStyleFiles() as $styleFile ) {
				$styles['screen'][] = new FilePath(
					$styleFile,
					$this->localBasePath,
					$this->remoteBasePath
				);
			}
		}

		return $styles;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getPreloadLinks( ResourceLoaderContext $context ): array {
		$preloadLinks = parent::getPreloadLinks( $context );

		$wordmark = MediaWikiServices::getInstance()->getService( 'Mirage.WordmarkLookup' )
			->getWordmarkUrl();

		if ( $wordmark !== null ) {
			$preloadLinks[$wordmark] = [ 'as' => 'image' ];
		}

		// Mirage doesn't use the default MediaWiki logo, but does support the wordmark.
		// Preload these manually. Use SkinModule::getAvailableLogos instead of
		// SkinModule::getLogoData because the latter transforms the path, causing the preload
		// link to mismatch and warnings to be shown in the browser console.
		$logo = self::getAvailableLogos( $this->getConfig(), $context->getLanguage() );

		// Substitute the svg, or 1x logo, for the icon if it is not defined.
		if ( !isset( $logo['icon'] ) ) {
			$logo['icon'] = $logo['svg'] ?? $logo['1x'];
		}

		$preloadLinks[$logo['icon']] = [ 'as' => 'image' ];

		// Substitute the src variant for the 1x wordmark if it is not defined.
		if ( !isset( $logo['wordmark']['1x'] ) && isset( $logo['wordmark']['src'] ) ) {
			$logo['wordmark']['1x'] = $logo['wordmark']['src'];
		}

		if ( isset( $logo['wordmark']['1x'] ) ) {
			$preloadLinks[$logo['wordmark']['1x']] = [ 'as' => 'image' ];
		}

		if ( isset( $logo['tagline']['src'] ) ) {
			$preloadLinks[$logo['tagline']['src']] = [ 'as' => 'image' ];
		}

		return $preloadLinks;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ): array {
		$summary = parent::getDefinitionSummary( $context );

		$wordmarkFile = MediaWikiServices::getInstance()->getService( 'Mirage.WordmarkLookup' )
			->getWordmarkFile();

		if ( $wordmarkFile ) {
			$summary[] = [
				'mirage-wordmark' => $wordmarkFile->getSha1()
			];
		}

		return $summary;
	}
}
