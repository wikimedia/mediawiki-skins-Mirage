<?php

namespace MediaWiki\Skins\Mirage;

use Config;
use function array_fill_keys;
use function strtolower;

class ThemeRegistry {
	public const THEMES = [
		'DarkMirage' => [
			'themes/DarkMirage/theme.less'
		],
		'Neutral' => [
			'themes/Neutral/theme.less'
		]
	];

	/** @var Config */
	private $config;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Config $mirageConfig
	 */
	public function __construct( Config $mirageConfig ) {
		$this->config = $mirageConfig;
	}

	/**
	 * Returns a list of theme style files to apply, based on the value of $wgMirageTheme.
	 *
	 * @return string[]
	 */
	public function getThemeStyleFiles() : array {
		$theme = $this->config->get( 'MirageTheme' );

		if ( $theme && isset( self::THEMES[$theme] ) ) {
			return self::THEMES[$theme];
		} else {
			return [];
		}
	}

	/**
	 * Builds ResourceLoader modules as expected by Extension:Theme.
	 *
	 * @return array
	 */
	public function buildResourceLoaderModuleDefinitions() : array {
		$definitions = [];

		foreach ( self::THEMES as $themeName => $styleFiles ) {
			// Extension:Theme expects the theme in lowercase.
			$themeName = strtolower( $themeName );
			$definitions["themeloader.skins.mirage.$themeName"] = [
				'targets' => [
					'desktop',
					'mobile'
				],
				'styles' => array_fill_keys( $styleFiles, [ 'media' => 'screen' ] ),
				'localBasePath' => __DIR__ . '/../resources',
				'remoteExtPath' => 'Mirage/resources',
			];
		}

		return $definitions;
	}
}
