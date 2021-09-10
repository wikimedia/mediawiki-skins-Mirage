<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use Generator;
use MediaWiki\Skins\Mirage\Tests\TemplateProcessor;
use MediaWikiUnitTestCase;
use function basename;
use function glob;
use function str_replace;

/**
 * @coversNothing
 */
class MustacheCodeStyleTest extends MediaWikiUnitTestCase {
	private const TEMPLATE_DIR = __DIR__ . '/../../../resources/templates';

	/**
	 * @dataProvider provideTemplates
	 *
	 * @param string $template
	 */
	public function testTemplateCodeStyle( string $template ): void {
		static::assertSame(
			[],
			( new TemplateProcessor( self::TEMPLATE_DIR, $template ) )->process()
		);
	}

	public static function provideTemplates(): Generator {
		foreach ( glob( self::TEMPLATE_DIR . '/*.mustache' ) as $template ) {
			yield basename( $template ) => [
				str_replace( self::TEMPLATE_DIR . '/', '', $template )
			];
		}
	}
}
