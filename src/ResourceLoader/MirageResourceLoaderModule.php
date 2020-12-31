<?php

namespace MediaWiki\Skins\Mirage\ResourceLoader;

use CSSMin;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Mirage\ThemeRegistry;
use ResourceLoaderContext;
use ResourceLoaderFilePath;
use ResourceLoaderSkinModule;

class MirageResourceLoaderModule extends ResourceLoaderSkinModule {
	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array[]
	 */
	public function getStyles( ResourceLoaderContext $context ) : array {
		$styles = parent::getStyles( $context );

		$wordmark = MediaWikiServices::getInstance()->getService( 'MirageWordmarkLookup' )
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
	public function getStyleFiles( ResourceLoaderContext $context ) : array {
		$styles = parent::getStyleFiles( $context );

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Theme' ) ) {
			$themeRegistry = new ThemeRegistry(
				MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'Mirage' )
			);

			foreach ( $themeRegistry->getThemeStyleFiles() as $styleFile ) {
				$styles['screen'][] = new ResourceLoaderFilePath(
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
	public function getPreloadLinks( ResourceLoaderContext $context ) : array {
		$preloadLinks = parent::getPreloadLinks( $context );

		$wordmark = MediaWikiServices::getInstance()->getService( 'MirageWordmarkLookup' )
			->getWordmarkUrl();

		if ( $wordmark !== null ) {
			$preloadLinks[$wordmark] = [ 'as' => 'image' ];
		}

		// Mirage doesn't use the default MediaWiki logo, but does support the wordmark.
		// Preload these manually.
		$logo = $this->getLogoData( $this->getConfig() );

		// Mirage falls back to the default logo when no icon is defined, so preload it if it
		// isn't defined.
		if ( !isset( $logo['icon'] ) ) {
			$logo['icon'] = $logo['svg'] ?? $logo['1x'];
		}

		$preloadLinks[$logo['icon']] = [ 'as' => 'image' ];

		if ( isset( $logo['wordmark'] ) ) {
			$preloadLinks[$logo['wordmark']] = [ 'as' => 'image' ];
		}

		if ( isset( $logo['tagline'] ) ) {
			$preloadLinks[$logo['tagline']] = [ 'as' => 'image' ];
		}

		return $preloadLinks;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ) : array {
		$summary = parent::getDefinitionSummary( $context );

		$wordmarkFile = MediaWikiServices::getInstance()->getService( 'MirageWordmarkLookup' )
			->getWordmarkFile();

		if ( $wordmarkFile ) {
			$summary[] = [
				'mirage-wordmark' => $wordmarkFile->getSha1()
			];
		}

		return $summary;
	}
}
