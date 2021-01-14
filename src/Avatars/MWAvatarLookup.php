<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use Avatar\Avatars;
use MediaWiki\User\UserIdentity;

class MWAvatarLookup extends AvatarLookup {
	/**
	 * @inheritDoc
	 */
	public function getAvatarForUser( UserIdentity $user ) : string {
		return Avatars::getLinkFor( $user->getName() );
	}
}
