<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use MediaWiki\Extension\Gravatar\GravatarLookup;
use MediaWiki\User\UserIdentity;

class GravatarAvatarLookup extends AvatarLookup {
	private GravatarLookup $gravatarLookup;

	/**
	 * @param GravatarLookup $gravatarLookup
	 */
	public function __construct( GravatarLookup $gravatarLookup ) {
		$this->gravatarLookup = $gravatarLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvatarForUser( UserIdentity $user ): string {
		return $this->gravatarLookup->getAvatarForUser( $user );
	}
}
