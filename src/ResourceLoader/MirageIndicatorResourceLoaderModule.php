<?php

namespace MediaWiki\Skins\Mirage\ResourceLoader;

use MediaWiki\ResourceLoader\OOUIImageModule;

/**
 * ResourceLoader module to load indicators from OOUI with custom classes.
 *
 * @codeCoverageIgnore
 */
class MirageIndicatorResourceLoaderModule extends OOUIImageModule {
	/**
	 * @inheritDoc
	 * Copied from ResourceLoader\OOUIIconPackModule.
	 * We need the indicators provided by core, so have the localBasePath point to $IP.
	 */
	public static function extractLocalBasePath( array $options, $localBasePath = null ) {
		global $IP;
		// Ignore any 'localBasePath' present in $options, this always refers to files in MediaWiki core
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable $IP is not null, phan.
		return $localBasePath ?? $IP;
	}
}
