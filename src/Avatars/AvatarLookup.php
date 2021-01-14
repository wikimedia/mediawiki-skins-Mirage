<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use MediaWiki\User\UserIdentity;

abstract class AvatarLookup {
	/**
	 * Retrieves the avatar of the given user.
	 *
	 * @param UserIdentity $user
	 * @return string CSS embeddable url for the avatar
	 */
	abstract public function getAvatarForUser( UserIdentity $user ) : string;
}
