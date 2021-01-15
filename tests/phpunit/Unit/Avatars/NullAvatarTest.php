<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit\Avatars;

use DomainException;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\User\UserIdentity;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup
 */
class NullAvatarTest extends MediaWikiUnitTestCase {
	public function testGetAvatarForUser() : void {
		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( NullAvatarLookup::class . ': No avatar backend available!' );

		( new NullAvatarLookup() )->getAvatarForUser(
			$this->createMock( UserIdentity::class )
		);
	}
}
