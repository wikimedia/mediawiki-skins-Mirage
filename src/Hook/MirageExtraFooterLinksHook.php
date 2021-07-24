<?php

namespace MediaWiki\Skins\Mirage\Hook;

use MediaWiki\Skins\Mirage\SkinMirage;

/**
 * @stable to implement
 */
interface MirageExtraFooterLinksHook {
	/**
	 * Called when rendering the footer links.
	 * This hook allows adding additional sections of footer links.
	 *
	 * Links are specified in an array where the key is the message key of the section, and the
	 * value is an associative array of the link name (used as part of the id) with the actual link.
	 *
	 * @param SkinMirage $skin
	 * @param array &$footerLinks Footer links
	 */
	public function onMirageExtraFooterLinks( SkinMirage $skin, array &$footerLinks ): void;
}
