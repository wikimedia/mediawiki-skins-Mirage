<?php

namespace MediaWiki\Skins\Mirage\Test\Unit\ResourceLoader;

use InvalidArgumentException;
use MediaWiki\ResourceLoader\Context as ResourceLoaderContext;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\ResourceLoader\MirageAvatarResourceLoaderModule;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Wikimedia\Minify\CSSMin;

/**
 * @covers \MediaWiki\Skins\Mirage\ResourceLoader\MirageAvatarResourceLoaderModule
 */
class MirageAvatarResourceLoaderModuleTest extends MediaWikiUnitTestCase {
	public function testConstruct(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'This ResourceLoader module can only be instantiated with an actual avatar lookup, not NullAvatarLookup!'
		);

		new MirageAvatarResourceLoaderModule(
			[],
			null,
			null,
			new NullAvatarLookup()
		);
	}

	public function testGetStyles(): void {
		$user = $this->createMock( User::class );

		$context = $this->createMock( ResourceLoaderContext::class );
		$context->method( 'getUserObj' )->willReturn( $user );

		$lookup = $this->createMock( AvatarLookup::class );
		$lookup
			->expects( static::once() )
			->method( 'getAvatarForUser' )
			->willReturn( '/avatar.png' )
			->with( $user );

		$url = CSSMin::buildUrlValue( '/avatar.png' );

		$module = new MirageAvatarResourceLoaderModule(
			[],
			'',
			'',
			$lookup
		);
		static::assertEquals(
			[
				'screen' => ".skin-mirage-avatar-holder::before { background-image: $url; }"
			],
			$module->getStyles( $context )
		);
	}
}
