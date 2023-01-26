<?php
// phpcs:ignoreFile
// Stubs for the avatar component from SocialProfile.

class wAvatar {
	public function __construct( int $userId, string $size ) {}

	public function getAvatarImage(): string {}
}

class SocialProfileFileBackend {
	public function __construct( string $container ) {}

	public function getFileHttpUrlFromName( string $fileName ): string {}
}
