<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\MWAvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\SocialProfileAvatarLookup;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;

return [
	'MirageWordmarkLookup' => function ( MediaWikiServices $services ) : MirageWordmarkLookup {
		return new MirageWordmarkLookup(
			$services->getTitleFactory(),
			$services->getRepoGroup(),
			$services->getConfigFactory()->makeConfig( 'Mirage' )->get( 'MirageEnableImageWordmark' )
		);
	},

	'MirageAvatarLookup' => function () : AvatarLookup {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Avatar' ) ) {
			return new MWAvatarLookup();
		// This should check against the ExtensionRegistry, but SocialProfile is not loaded
		// through wfLoadExtension, and the avatar component is not a sub-extension.
		} elseif ( class_exists( '\wAvatar' ) ) {
			return new SocialProfileAvatarLookup();
		} else {
			return new NullAvatarLookup();
		}
	}
];
