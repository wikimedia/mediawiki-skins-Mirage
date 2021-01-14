<?php

namespace MediaWiki\Skins\Mirage\Avatars;

use MediaWiki\User\UserIdentity;
use wAvatar;

class SocialProfileAvatarLookup extends AvatarLookup {
	/** @var string */
	private $uploadPath;

	/**
	 * @param string $UploadBaseUrl $wgUploadBaseUrl
	 * @param string $UploadPath $wgUploadPath
	 */
	public function __construct( string $UploadBaseUrl, string $UploadPath ) {
		$this->uploadPath = $UploadBaseUrl ? $UploadBaseUrl . $UploadPath : $UploadPath;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvatarForUser( UserIdentity $user ) : string {
		// getAvatarURL returns an <img>.
		$avatarImage = ( new wAvatar( $user->getId(), 'ml' ) )->getAvatarImage();

		return "{$this->uploadPath}/avatars/{$avatarImage}";
	}
}
