<?php

namespace MediaWiki\Skins\Mirage\ResourceLoader;

use ResourceLoaderOOUIImageModule;

/**
 * ResourceLoader module to load indicators from OOUI with custom classes.
 *
 * @codeCoverageIgnore
 */
class MirageIndicatorResourceLoaderModule extends ResourceLoaderOOUIImageModule {
	/**
	 * @inheritDoc
	 * Copied from ResourceLoaderOOUIIconPackModule.
	 * We need the indicators provided by core, so have the localBasePath point to $IP.
	 */
	public static function extractLocalBasePath( array $options, $localBasePath = null ) {
		global $IP;
		if ( $localBasePath === null ) {
			$localBasePath = $IP;
		}
		// Ignore any 'localBasePath' present in $options, this always refers to files in MediaWiki core
		return $localBasePath;
	}
}
