<?php

namespace MediaWiki\Skins\Mirage\Hook;

use MediaWiki\Context\IContextSource;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Skins\Mirage\SkinMirage;

class HookRunner implements
	MirageBuildNavigationHook,
	MirageBuildSiteToolsHook,
	MirageGetExtraIconsHook,
	MirageGetRightRailModulesHook,
	MirageExtraFooterLinksHook
{

	/**
	 * @var HookContainer
	 */
	private HookContainer $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onMirageBuildNavigation( SkinMirage $skin, array &$navigation ): void {
		$this->hookContainer->run(
			'MirageBuildNavigation',
			[ $skin, &$navigation ],
			[ 'abortable' => false ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMirageBuildSiteTools( IContextSource $context, array &$tools ): void {
		$this->hookContainer->run(
			'MirageBuildSiteTools',
			[ $context, &$tools ],
			[ 'abortable' => false ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMirageGetExtraIcons( array &$icons ): void {
		$this->hookContainer->run(
			'MirageGetExtraIcons',
			[ &$icons ],
			[ 'abortable' => false ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMirageExtraFooterLinks( SkinMirage $skin, array &$footerLinks ): void {
		$this->hookContainer->run(
			'MirageExtraFooterLinks',
			[ $skin, &$footerLinks ],
			[ 'abortable' => false ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMirageGetRightRailModules( IContextSource $context, array &$modules ): void {
		$this->hookContainer->run(
			'MirageGetRightRailModules',
			[ $context, &$modules ],
			[ 'abortable' => false ]
		);
	}
}
