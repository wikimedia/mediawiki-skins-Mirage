<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;

return [
	'MirageWordmarkLookup' => function ( MediaWikiServices $services ) : MirageWordmarkLookup {
		return new MirageWordmarkLookup(
			$services->getTitleFactory(),
			$services->getRepoGroup(),
			$services->getConfigFactory()->makeConfig( 'Mirage' )->get( 'MirageEnableImageWordmark' )
		);
	}
];
