<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use DomainException;
use MediaWiki\User\UserIdentity;

class NullAvatarLookup extends AvatarLookup {
	/**
	 * @inheritDoc
	 * @throws DomainException
	 * @suppress PhanPluginNeverReturnMethod LSP violation
	 */
	public function getAvatarForUser( UserIdentity $user ): string {
		throw new DomainException( self::class . ': No avatar backend available!' );
	}
}
