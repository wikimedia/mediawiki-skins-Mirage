<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use File;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWikiUnitTestCase;
use RepoGroup;
use TitleFactory;

/**
 * @covers \MediaWiki\Skins\Mirage\MirageWordmarkLookup
 */
class MirageWordmarkLookupTest extends MediaWikiUnitTestCase {
	public function testGetWordmarkUrlWithWordmarkDisabled(): void {
		$lookup = new MirageWordmarkLookup(
			$this->createMock( TitleFactory::class ),
			$this->createMock( RepoGroup::class ),
			false
		);

		static::assertNull( $lookup->getWordmarkUrl() );
	}

	public function testGetWordmarkUrlWithNoFileFound(): void {
		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFile' )->willReturn( false );

		$lookup = new MirageWordmarkLookup(
			$this->createMock( TitleFactory::class ),
			$repoGroup,
			true
		);

		static::assertNull( $lookup->getWordmarkUrl() );
	}

	public function testGetWordmarkUrl(): void {
		$file = $this->createMock( File::class );
		$file->method( 'exists' )->willReturn( true );
		$file->method( 'getUrl' )->willReturn( '/url.png' );

		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFile' )->willReturn( $file );

		$lookup = new MirageWordmarkLookup(
			$this->createMock( TitleFactory::class ),
			$repoGroup,
			true
		);

		static::assertSame( $lookup->getWordmarkUrl(), '/url.png' );
	}
}
