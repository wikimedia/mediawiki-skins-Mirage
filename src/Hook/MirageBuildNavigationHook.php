<?php

namespace MediaWiki\Skins\Mirage\Hook;

use MediaWiki\Skins\Mirage\SkinMirage;

/**
 * @stable to implement
 */
interface MirageBuildNavigationHook {
	/**
	 * This hook is called when extracting the navigation from Mediawiki:Mirage-navigation.
	 *
	 * @param SkinMirage $skin
	 * @param array &$navigation Navigation contents
	 */
	public function onMirageBuildNavigation( SkinMirage $skin, array &$navigation ): void;
}
