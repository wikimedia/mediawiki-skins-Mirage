<?php

namespace MediaWiki\Skins\Mirage;

use Html;
use HtmlArmor;
use function implode;

class MirageIcon {
	protected const ICON_MEDIUM = 'medium';
	protected const ICON_SMALL = 'small';
	public const ICON_PLACEHOLDER = 'placeholder';

	/** @var string */
	private $icon;

	/** @var string */
	private $size;

	/** @var string|HtmlArmor */
	private $content;

	/** @var string */
	private $variant;

	/** @var string[] */
	private $classes;

	/** @var string[] */
	private $attributes;

	/** @var string */
	private $element;

	/** @var bool */
	private $hideLabel;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param string $icon
	 * @param string $size
	 */
	protected function __construct( string $icon, string $size ) {
		$this->icon = $icon;
		$this->size = $size;
		$this->content = '';
		$this->variant = '';
		$this->classes = [];
		$this->attributes = [];
		$this->element = 'span';
		$this->hideLabel = false;
	}

	/**
	 * Hide or unhide the label accompanying the icon.
	 * Hidden labels will still be visible to screen readers.
	 *
	 * @param bool $hide
	 * @return static
	 */
	public function hideLabel( bool $hide = true ) : self {
		$this->hideLabel = $hide;

		return $this;
	}

	/**
	 * Set the content of the icon.
	 * Strings will be escaped. To set HTML as the content, provide it as HtmlArmor.
	 *
	 * @param HtmlArmor|string $content
	 * @return static
	 */
	public function setContent( $content ) : self {
		$this->content = $content;

		return $this;
	}

	/**
	 * Set the icon variant.
	 * Currently only 'inverted' is an accepted variant.
	 * To use the default, pass an empty string.
	 *
	 * @param string $variant
	 * @return static
	 */
	public function setVariant( string $variant ) : self {
		$this->variant = $variant;

		return $this;
	}

	/**
	 * @param string ...$classes
	 * @return static
	 */
	public function setClasses( string ...$classes ) : self {
		$this->classes = $classes;

		return $this;
	}

	/**
	 * @param array $attributes
	 * @return $this
	 */
	public function setAttributes( array $attributes ) : self {
		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * Set the element to use when building the HTML for this icon.
	 *
	 * @param string $element
	 * @return $this
	 */
	public function setElement( string $element ) : self {
		$this->element = $element;

		return $this;
	}

	/**
	 * Generates the appropriate html for rendering the icon.
	 *
	 * @return string
	 */
	public function __toString() : string {
		return Html::rawElement(
			$this->element,
			[ 'class' => $this->toClasses() ] + $this->attributes,
			HtmlArmor::getHtml( $this->content )
		);
	}

	/**
	 * Get the icon classes to use on existing elements.
	 *
	 * @return string
	 */
	public function toClasses() : string {
		$icon = $this->variant ? "$this->icon-$this->variant" : $this->icon;

		$classes = $this->classes;
		$classes[] = 'skin-mirage-ooui-icon';
		$classes[] = "skin-mirage-ooui-icon-$icon";
		$classes[] = "skin-mirage-ooui-icon-$this->size";

		if ( $this->hideLabel ) {
			$classes[] = 'skin-mirage-ooui-icon-no-label';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Create a small sized icon.
	 *
	 * @param string $icon
	 * @return static
	 */
	public static function small( string $icon ) : self {
		return new self( $icon, self::ICON_SMALL );
	}

	/**
	 * Create a medium sized icon.
	 *
	 * @param string $icon
	 * @return static
	 */
	public static function medium( string $icon ) : self {
		return new self( $icon, self::ICON_MEDIUM );
	}
}
