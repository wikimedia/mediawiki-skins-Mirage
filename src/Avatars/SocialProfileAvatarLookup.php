<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use MediaWiki\User\UserIdentity;
use SocialProfileFileBackend;
use wAvatar;

class SocialProfileAvatarLookup extends AvatarLookup {
	/**
	 * @inheritDoc
	 */
	public function getAvatarForUser( UserIdentity $user ): string {
		$backend = new SocialProfileFileBackend( 'avatars' );
		return $backend->getFileHttpUrlFromName( ( new wAvatar( $user->getId(), 'ml' ) )->getAvatarImage() );
	}
}
