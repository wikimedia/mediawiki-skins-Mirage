<?php

namespace MediaWiki\Skins\Mirage\ResourceLoader;

use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Mirage\Hook\HookRunner;
use ResourceLoaderContext;
use ResourceLoaderImage;
use ResourceLoaderOOUIIconPackModule;
use function array_merge;
use function implode;
use function is_string;
use function strtr;

class MirageIconResourceLoaderModule extends ResourceLoaderOOUIIconPackModule {
	/** @var array */
	private $icons;

	/**
	 * @inheritDoc
	 */
	public function __construct( array $options = [], $localBasePath = null ) {
		parent::__construct( $options, $localBasePath );

		$this->icons = $options['icons'];

		$extraIcons = [];

		( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )
			->onMirageGetExtraIcons( $extraIcons );

		foreach ( $extraIcons as $icon => $description ) {
			if ( !is_string( $icon ) ) {
				$icon = $description;
				$description = [];
			}

			if ( !isset( $this->icons[$icon] ) ) {
				$this->icons[$icon] = $description;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getImages( ResourceLoaderContext $context ) : array {
		$skin = $context->getSkin();
		if ( $this->imageObjects === null ) {
			$this->loadFromDefinition();
			$this->imageObjects = [];
		}
		if ( !isset( $this->imageObjects[$skin] ) ) {
			$this->imageObjects[$skin] = [];
			if ( !isset( $this->images[$skin] ) ) {
				$this->images[$skin] = $this->images['default'] ?? [];
			}
			foreach ( $this->images[$skin] as $name => $options ) {
				$fileDescriptor = is_array( $options ) ? $options['file'] : $options;

				$allowedVariants = array_merge(
					$options['variants'] ?? [],
					$this->icons[$name]['variants'] ?? [],
					$this->getGlobalVariants( $context )
				);
				if ( isset( $this->variants[$skin] ) ) {
					$variantConfig = array_intersect_key(
						$this->variants[$skin],
						array_fill_keys( $allowedVariants, true )
					);
				} else {
					$variantConfig = [];
				}

				$image = new ResourceLoaderImage(
					$name,
					$this->getName(),
					$fileDescriptor,
					$this->localBasePath,
					$variantConfig,
					$this->defaultColor
				);
				$this->imageObjects[$skin][$image->getName()] = $image;
			}
		}

		return $this->imageObjects[$skin];
	}

	/**
	 * Copy of ResourceLoaderImageModule::getStyleDeclarations, which is private.
	 *
	 * @param ResourceLoaderContext $context
	 * @param ResourceLoaderImage $image
	 * @param string $script
	 * @param string|null $variant
	 * @return string
	 */
	private function getStyleDeclarations(
		ResourceLoaderContext $context,
		ResourceLoaderImage $image,
		string $script,
		?string $variant = null
	) : string {
		$imageDataUri = $this->useDataURI ? $image->getDataUri( $context, $variant, 'original' ) : false;
		$primaryUrl = $imageDataUri ?: $image->getUrl( $context, $script, $variant, 'original' );
		$declarations = $this->getCssDeclarations(
			$primaryUrl,
			$image->getUrl( $context, $script, $variant, 'rasterized' )
		);
		return implode( "\n\t", $declarations );
	}

	/**
	 * @inheritDoc
	 */
	public function getStyles( ResourceLoaderContext $context ) : array {
		$this->loadFromDefinition();

		// Build CSS rules
		$rules = [];
		$script = $context->getResourceLoader()->getLoadScript( $this->getSource() );
		$selectors = $this->getSelectors();

		foreach ( $this->getImages( $context ) as $name => $image ) {
			$declarations = $this->getStyleDeclarations( $context, $image, $script );
			$selector = strtr(
				$selectors['selectorWithoutVariant'],
				[
					'{prefix}' => $this->getPrefix(),
					'{name}' => $name,
					'{variant}' => '',
				]
			);

			if ( isset( $this->icons[$name]['selectorWithoutVariant'] ) ) {
				$selector = "$selector,\n" . implode(
				",\n",
					(array)$this->icons[$name]['selectorWithoutVariant']
				);
			}

			$rules[] = "$selector {\n\t$declarations\n}";

			foreach ( $image->getVariants() as $variant ) {
				$declarations = $this->getStyleDeclarations( $context, $image, $script, $variant );
				$selector = strtr(
					$selectors['selectorWithVariant'],
					[
						'{prefix}' => $this->getPrefix(),
						'{name}' => $name,
						'{variant}' => $variant,
					]
				);

				if ( isset( $this->icons[$name]['selectorWithVariant'][$variant] ) ) {
					$selector = "$selector,\n" . implode(
						",\n",
						(array)$this->icons[$name]['selectorWithVariant'][$variant]
					);
				}

				$rules[] = "$selector {\n\t$declarations\n}";
			}
		}

		$style = implode( "\n", $rules );
		return [ 'all' => $style ];
	}

	/**
	 * @inheritDoc
	 */
	protected function loadOOUIDefinition( $theme, $unused ) : array {
		// This is shared between instances of this class, so we only have to load the JSON files once
		static $data = [];

		if ( !isset( $data[$theme] ) ) {
			$data[$theme] = [];
			// Load and merge the JSON data for all "icons-foo" modules
			foreach ( self::$knownImagesModules as $module ) {
				if ( substr( $module, 0, 5 ) === 'icons' ) {
					$moreData = $this->readJSONFile( $this->getThemeImagesPath( $theme, $module ) );
					if ( $moreData ) {
						$data[$theme] = array_replace_recursive( $data[$theme], $moreData );
					}
				}
			}
		}

		$definition = $data[$theme];

		// Filter out the data for all other icons, leaving only the ones we want for this module.
		foreach ( array_keys( $definition['images'] ) as $iconName ) {
			if ( !isset( $this->icons[$iconName] ) ) {
				unset( $definition['images'][$iconName] );
			}
		}

		return $definition;
	}

	/**
	 * @inheritDoc
	 */
	public static function extractLocalBasePath( array $options, $localBasePath = null ) : ?string {
		global $IP;

		// Ignore any 'localBasePath' present in $options, this always refers to files in MediaWiki core
		return $localBasePath ?? $IP;
	}
}
