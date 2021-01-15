<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use MediaWiki\User\UserIdentity;
use wAvatar;

class SocialProfileAvatarLookup extends AvatarLookup {
	/**
	 * @inheritDoc
	 */
	public function getAvatarForUser( UserIdentity $user ) : string {
		return ( new wAvatar( $user->getId(), 'ml' ) )->getAvatarUrlPath();
	}
}
