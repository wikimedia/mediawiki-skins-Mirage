<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use MediaWiki\Html\Html;
use MediaWiki\Skins\Mirage\SkinMirage;

class GenericItemListModule extends RightRailModule {
	private array $items;

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
	protected function getBodyContent(): string {
		$skin = $this->getSkin();

		$html = Html::openElement( 'ul', [ 'class' => 'skin-mirage-unstyled-list' ] );

		foreach ( $this->items as $name => $item ) {
			$html .= $skin->makeListItem( $name, $item );
		}

		return $html . Html::closeElement( 'ul' );
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown(): bool {
		return $this->items !== [];
	}

	/**
	 * @inheritDoc
	 */
	protected function getRole(): string {
		return 'navigation';
	}
}
