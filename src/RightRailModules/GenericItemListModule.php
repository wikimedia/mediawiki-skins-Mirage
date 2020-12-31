<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use Html;
use MediaWiki\Skins\Mirage\SkinMirage;

class GenericItemListModule extends RightRailModule {
	/** @var array */
	private $items;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param SkinMirage $skin
	 * @param string $name Module name
	 * @param array $items List items to display
	 */
	public function __construct( SkinMirage $skin, string $name, array $items ) {
		parent::__construct( $skin, $name );

		$this->items = $items;
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent() : string {
		$skin = $this->getSkin();

		$html = Html::openElement( 'ul', [ 'class' => 'unstyled-list' ] );

		foreach ( $this->items as $name => $item ) {
			$html .= $skin->makeListItem( $name, $item );
		}

		return $html . Html::closeElement( 'ul' );
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown() : bool {
		return $this->items !== [];
	}

	/**
	 * @inheritDoc
	 */
	protected function getRole() : string {
		return 'navigation';
	}
}
