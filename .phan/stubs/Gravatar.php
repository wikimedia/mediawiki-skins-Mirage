<?php
// phpcs:ignoreFile
// Stubs for the Gravatar extension.

namespace MediaWiki\Extension\Gravatar;

use MediaWiki\User\UserIdentity;

class GravatarLookup {
	public function getAvatarForUser( UserIdentity $userIdentity, int $size = 0 ) : string {}
}
