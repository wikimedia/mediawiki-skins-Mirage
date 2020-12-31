<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use Generator;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\Skins\Mirage\Tests\TemplateProcessor;
use MediaWikiUnitTestCase;
use function basename;
use function glob;
use function str_replace;

/**
 * @coversNothing
 */
class MustacheCodeStyleTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideTemplates
	 *
	 * @param string $template
	 */
	public function testTemplateCodeStyle( string $template ) : void {
		static::assertSame(
			[],
			( new TemplateProcessor( SkinMirage::TEMPLATE_DIR, $template ) )->process()
		);
	}

	public static function provideTemplates() : Generator {
		foreach ( glob( SkinMirage::TEMPLATE_DIR . '/*.mustache' ) as $template ) {
			yield basename( $template ) => [
				str_replace( SkinMirage::TEMPLATE_DIR . '/', '', $template )
			];
		}
	}
}
