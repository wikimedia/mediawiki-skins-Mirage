<?php

namespace MediaWiki\Skins\Mirage\Hook;

/**
 * @stable to implement
 */
interface MirageGetExtraIconsHook {
	/**
	 * Extends the icons supplied by the skin.mirage.icons ResourceLoaderModule.
	 *
	 * @param string[] &$icons Icon names, optionally mapped to an array describing the supported
	 * variants and custom selectors. See skin.json for examples.
	 */
	public function onMirageGetExtraIcons( array &$icons ): void;
}
