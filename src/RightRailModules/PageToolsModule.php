<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use MediaWiki\Skins\Mirage\SkinMirage;
use function array_diff_key;
use function array_fill_keys;

class PageToolsModule extends GenericItemListModule {
	/**
	 * @param SkinMirage $skin
	 * @param array $toolbox
	 */
	public function __construct( SkinMirage $skin, array $toolbox ) {
		parent::__construct(
			$skin,
			'tb',
			array_diff_key(
				$toolbox,
				// User tools (when present) are handled by the UserTools module.
				array_fill_keys( UserToolsModule::USER_TOOLS, true ),
				// Feeds are provided in the footer, upload, special pages and printable
				// version in the site tools.
				[
					'feeds' => true,
					'print' => true,
					'specialpages' => true,
					'upload' => true
				]
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader() : string {
		return $this->msg( 'mirage-page-tools' )->escaped();
	}
}
