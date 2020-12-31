<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use MediaWiki\Skins\Mirage\SkinMirage;
use function array_fill_keys;
use function array_intersect_key;

class UserToolsModule extends GenericItemListModule {

	public const USER_TOOLS = [
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
				array_fill_keys( self::USER_TOOLS, true )
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader() : string {
		return $this->msg( 'mirage-user-tools' )->escaped();
	}
}
