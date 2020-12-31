<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use Html;
use MediaWiki\Skins\Mirage\SkinMirage;

class LanguageLinksModule extends RightRailModule {
	/** @var array */
	private $languageLinks;

	/** @var string */
	private $afterPortlet;

	/**
	 * @param SkinMirage $skin
	 * @param array $languageLinks
	 */
	public function __construct( SkinMirage $skin, array $languageLinks ) {
		parent::__construct( $skin, 'lang' );

		$this->languageLinks = $languageLinks;
		$this->afterPortlet = $skin->getAfterPortlet( 'lang' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent() : string {
		$skin = $this->getSkin();

		$html = '';

		if ( $this->languageLinks ) {
			$html = Html::openElement( 'ul', [ 'class' => 'unstyled-list' ] );

			foreach ( $this->languageLinks as $name => $link ) {
				$html .= $skin->makeListItem( $name, $link );
			}

			$html .= Html::closeElement( 'ul' );
		}

		return $html;
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown() : bool {
		// A few extensions rely on incorrect manipulation of SkinTemplate to forcibly
		// display the languages sidebar portlet.
		return $this->languageLinks !== [] || $this->afterPortlet !== '';
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader() : string {
		return $this->msg( 'otherlanguages' )->escaped();
	}

	/**
	 * @inheritDoc
	 */
	protected function getRole() : string {
		return 'navigation';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAfterModuleContent() : string {
		return $this->afterPortlet;
	}
}
