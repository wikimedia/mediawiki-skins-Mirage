<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use HtmlArmor;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Skins\Mirage\SkinMirage;
use Message;
use MessageLocalizer;
use MessageSpecifier;
use function implode;

/**
 * @stable to extend
 */
abstract class RightRailModule implements MessageLocalizer {

	private SkinMirage $skin;

	private string $name;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param SkinMirage $skin
	 * @param string $name
	 */
	public function __construct( SkinMirage $skin, string $name ) {
		$this->skin = $skin;
		$this->name = $name;
	}

	/**
	 * @return SkinMirage
	 */
	protected function getSkin(): SkinMirage {
		return $this->skin;
	}

	/**
	 * Get the body content for this module.
	 *
	 * @return string
	 */
	abstract protected function getBodyContent(): string;

	/**
	 * Returns the message to use for the header of this module.
	 * When null is returned, the header is omitted.
	 *
	 * @return string|null
	 */
	protected function getHeader(): ?string {
		$msg = $this->msg( $this->name );

		return !$msg->isDisabled() ? $msg->escaped() : HtmlArmor::getHtml( $this->name );
	}

	/**
	 * Returns the HTML 5 role attribute for this module.
	 * By default the attribute is omitted.
	 *
	 * @return string|null
	 */
	protected function getRole(): ?string {
		return null;
	}

	/**
	 * Returns the content to place after the module body.
	 *
	 * @return string
	 */
	protected function getAfterModuleContent(): string {
		return $this->skin->getAfterPortlet( $this->name );
	}

	/**
	 * Return the classes to go on the section element.
	 *
	 * @return array
	 */
	protected function getAdditionalModuleClasses(): array {
		return [ 'skin-mirage-styled-right-rail-module' ];
	}

	/**
	 * Indicates if this module can be shown.
	 * Override this method if the module depends on something that might not be available,
	 * or when its content is not suitable for the given title.
	 *
	 * Determining if a module should be shown should typically be done in
	 * @see RightRailBuilder::determineModules(), or in the MirageGetRightRailModules hook.
	 *
	 * @return bool
	 */
	public function canBeShown(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param MessageSpecifier|string|string[] $key
	 * @param mixed ...$params
	 * @return Message
	 */
	public function msg( $key, ...$params ): Message {
		return $this->skin->msg( $key, ...$params );
	}

	/**
	 * Returns the template parameters for rendering this module.
	 *
	 * @return array
	 */
	final public function getTemplateParameters(): array {
		$header = $this->getHeader();

		return [
			'html-module-id' => Sanitizer::escapeIdForAttribute( $this->name ),
			// Mustache doesn't seem to pick up on parameters in the same scope unless given an array.
			'html-header' => $header ? [ $header ] : null,
			'html-body-content' => $this->getBodyContent(),
			'html-role' => $this->getRole(),
			'html-additional-classes' => implode( ' ', $this->getAdditionalModuleClasses() ) ?: null,
			'html-after-module' => $this->getAfterModuleContent() ?: null
		];
	}
}
