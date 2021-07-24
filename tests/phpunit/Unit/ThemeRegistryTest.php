<?php

namespace MediaWiki\Skins\Mirage\Tests\Unit;

use Generator;
use HashConfig;
use MediaWiki\Skins\Mirage\ThemeRegistry;
use MediaWikiUnitTestCase;

class ThemeRegistryTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Skins\Mirage\ThemeRegistry::getThemeStyleFiles
	 *
	 * @dataProvider provideThemes
	 *
	 * @param string $theme
	 * @param array $expected
	 */
	public function testGetThemeStyleFiles( string $theme, array $expected ): void {
		$registry = new ThemeRegistry( new HashConfig( [
			'MirageTheme' => $theme
		] ) );

		$this->assertArrayEquals(
			$expected,
			$registry->getThemeStyleFiles()
		);
	}

	/**
	 * Data provider for testGetThemeStyleFiles.
	 *
	 * @return Generator
	 */
	public function provideThemes(): Generator {
		foreach ( ThemeRegistry::THEMES as $theme => $styles ) {
			yield $theme => [ $theme, $styles ];
		}

		yield 'Not a theme' => [ 'Not a theme', [] ];
	}

	/**
	 * @covers \MediaWiki\Skins\Mirage\ThemeRegistry::buildResourceLoaderModuleDefinitions
	 */
	public function testBuildResourceLoaderModuleDefinitions(): void {
		$registry = new ThemeRegistry( new HashConfig() );

		$actual = $registry->buildResourceLoaderModuleDefinitions();

		unset(
			$actual['themeloader.skins.mirage.darkmirage']['localBasePath'],
			$actual['themeloader.skins.mirage.neutral']['localBasePath']
		);

		$this->assertArrayEquals(
			[
				'themeloader.skins.mirage.darkmirage' => [
					'targets' => [
						'desktop',
						'mobile'
					],
					'styles' => [
						'themes/DarkMirage/theme.less' => [ 'media' => 'screen' ]
					],
					'remoteExtPath' => 'Mirage/resources'
				],
				'themeloader.skins.mirage.neutral' => [
					'targets' => [
						'desktop',
						'mobile'
					],
					'styles' => [
						'themes/Neutral/theme.less' => [ 'media' => 'screen' ]
					],
					'remoteExtPath' => 'Mirage/resources'
				]
			],
			$actual
		);
	}
}
