<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use ExtensionRegistry;
use MediaWiki\Skins\Mirage\SkinMirage;
use function array_fill_keys;
use function array_intersect_key;

class UserToolsModule extends GenericItemListModule {

	private const USER_TOOLS = [
		'contributions',
		'log',
		'blockip',
		'emailuser',
		'mute',
		'userrights'
	];

	/**
	 * @param SkinMirage $skin
	 * @param array $toolbox
	 */
	public function __construct( SkinMirage $skin, array $toolbox ) {
		parent::__construct(
			$skin,
			'user-tools',
			array_intersect_key(
				$toolbox,
				array_fill_keys( self::getUserTools(), true )
			)
		);
	}

	/**
	 * Get the tools that should be in the UserToolsModule.
	 *
	 * @return array
	 */
	public static function getUserTools(): array {
		return array_merge(
			self::USER_TOOLS,
			ExtensionRegistry::getInstance()->getAttribute( 'MirageExtraUserTools' )
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader(): string {
		return $this->msg( 'mirage-user-tools' )->escaped();
	}
}
