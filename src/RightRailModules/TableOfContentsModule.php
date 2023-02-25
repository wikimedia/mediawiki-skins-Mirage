<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use MediaWiki\Html\Html;
use MediaWiki\Skins\Mirage\SkinMirage;
use Wikimedia\Parsoid\Core\SectionMetadata;
use Wikimedia\Parsoid\Core\TOCData;

class TableOfContentsModule extends RightRailModule {

	private TOCData $TOCData;

	private int $maxTocLevel;

	/**
	 * @param SkinMirage $skin
	 * @param TOCData $TOCData
	 * @param int $maxTocLevel
	 */
	public function __construct( SkinMirage $skin, TOCData $TOCData, int $maxTocLevel ) {
		parent::__construct( $skin, 'mirage-toc' );

		$this->TOCData = $TOCData;
		$this->maxTocLevel = $maxTocLevel;
	}

	/** @inheritDoc */
	protected function getBodyContent(): string {
		return $this->makeTocTree( $this->TOCData->getSections() );
	}

	/**
	 * @param SectionMetadata[] $sections
	 * @param int $tocLevel
	 * @return string
	 */
	private function makeTocTree( array $sections, int $tocLevel = 1 ): string {
		if ( $tocLevel > $this->maxTocLevel ) {
			return '';
		}

		$data = Html::openElement( 'ul', [ 'class' => 'skin-mirage-unstyled-list' ] );

		foreach ( $sections as $i => $section ) {
			if ( $section->tocLevel < $tocLevel ) {
				return $data . Html::closeElement( 'ul' );
			}

			if ( $section->tocLevel === $tocLevel ) {
				$childSections = $this->makeTocTree(
					array_slice( $sections, $i + 1 ),
					$tocLevel + 1
				);

				$data .= Html::rawElement(
					'li',
					[],
					Html::rawElement(
						'a',
						[ 'href' => "#{$section->linkAnchor}" ],
						// This is already HTML-escaped, don't do it twice.
						$section->line
					) .
					$childSections
				);
			}
		}

		return $data . Html::closeElement( 'ul' );
	}

	/** @inheritDoc */
	protected function getHeader(): string {
		return $this->msg( 'toc' )->escaped();
	}

	/** @inheritDoc */
	protected function getRole(): string {
		return 'navigation';
	}

	/** @inheritDoc */
	public function canBeShown(): bool {
		return (bool)$this->TOCData->getSections();
	}
}
