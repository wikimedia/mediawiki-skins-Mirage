<?php

namespace MediaWiki\Skins\Mirage\Hook;

use MediaWiki\Context\IContextSource;

/**
 * @stable to implement
 */
interface MirageGetRightRailModulesHook {
	/**
	 * This hook allows changing which right rail modules are shown for the given context.
	 *
	 * @param IContextSource $context
	 * @param array[] &$modules List of module ObjectFactory specs keyed to the module name
	 */
	public function onMirageGetRightRailModules( IContextSource $context, array &$modules ): void;
}
