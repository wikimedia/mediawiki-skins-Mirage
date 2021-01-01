<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\User\UserOptionsLookup;
use TitleFactory;

class CategoriesModule extends RightRailModule {
	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var bool */
	private $displayHiddenCategories;

	/** @var array */
	private $allCategories;

	/**
	 * @param SkinMirage $skin
	 * @param LinkRenderer $linkRenderer
	 * @param TitleFactory $titleFactory
	 * @param UserOptionsLookup $optionsLookup
	 */
	public function __construct(
		SkinMirage $skin,
		LinkRenderer $linkRenderer,
		TitleFactory $titleFactory,
		UserOptionsLookup $optionsLookup
	) {
		parent::__construct( $skin, 'categories' );

		$this->linkRenderer = $linkRenderer;
		$this->titleFactory = $titleFactory;
		$this->displayHiddenCategories = $optionsLookup->getBoolOption(
			$skin->getUser(),
			'showhiddencats'
		);
		$this->allCategories = $skin->getOutput()->getCategoryLinks();
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader() : string {
		return $this->linkRenderer->makeLink(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->titleFactory->newFromText(
				$this->msg( 'pagecategorieslink' )->inContentLanguage()->text()
			),
			$this->msg( 'pagecategories' )->numParams( 2 )->text()
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getAdditionalModuleClasses() : array {
		$classes = parent::getAdditionalModuleClasses();

		$classes[] = 'catlinks';

		return $classes;
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown() : bool {
		return !empty( $this->allCategories['normal'] ) ||
			( $this->displayHiddenCategories && !empty( $this->allCategories['hidden'] ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent() : string {
		$html = '';

		if ( !empty( $this->allCategories['normal'] ) ) {
			$html .= $this->renderCategories(
				$this->allCategories['normal'],
				'mw-normal-catlinks'
			);
		}

		if ( !empty( $this->allCategories['hidden'] ) && $this->displayHiddenCategories ) {
			$html .= Html::element(
				'h4',
				[],
				$this->msg( 'mirage-hidden-categories' )->plain()
			) . $this->renderCategories(
				$this->allCategories['hidden'],
				'mw-hidden-catlinks'
			);
		}

		return Html::rawElement(
			'div',
			[
				'id' => 'catlinks',
				'data-mw' => 'interface'
			],
			$html
		);
	}

	/**
	 * Render a block with categories.
	 *
	 * @param string[] $categories
	 * @param string $id Wrapper Id (and class)
	 * @return string
	 */
	private function renderCategories( array $categories, string $id ) : string {
		$html = Html::openElement( 'div', [
				'id' => $id,
				// It just so happens the id and the class are the same.
				'class' => $id
			] ) .
			Html::openElement( 'ul', [
				'class' => 'skin-mirage-unstyled-list'
			] );

		foreach ( $categories as $categoryLink ) {
			$html .= Html::rawElement(
				'li',
				[],
				$categoryLink
			);
		}

		return $html .
			Html::closeElement( 'ul' ) .
			Html::closeElement( 'div' );
	}
}
