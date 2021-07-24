<?php

namespace MediaWiki\Skins\Mirage\Hook;

use IContextSource;

/**
 * @stable to implement
 */
interface MirageBuildSiteToolsHook {
	/**
	 * Specifies links to site tools in the header.
	 *
	 * The array structure used by $tools is the same as expected by Skin::makeListItem, except
	 * that it also supports an 'icon' field, which allows specifying an icon defined in
	 * skin.mirage.icons. To add extra icons, use the MirageGetExtraIcons hook.
	 *
	 * @param IContextSource $context
	 * @param array &$tools
	 */
	public function onMirageBuildSiteTools( IContextSource $context, array &$tools ): void;
}
