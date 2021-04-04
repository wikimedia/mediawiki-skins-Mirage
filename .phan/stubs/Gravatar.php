<?php
// phpcs:ignoreFile
// Stubs for the Gravatar extension.

namespace MediaWiki\Extensions\Gravatar;

use MediaWiki\User\UserIdentity;

class GravatarLookup {
	public function getAvatarForUser( UserIdentity $userIdentity, int $size = 0 ) : string {}
}
