<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWExceptionHandler;
use MWTimestamp;
use RecentChange;
use stdClass;
use TitleValue;
use User;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;
use const DB_REPLICA;
use const NS_SPECIAL;
use const NS_USER;

class RecentChangesModule extends RightRailModule {
	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var SpecialPageFactory */
	private $specialPageFactory;

	/** @var IResultWrapper|null */
	private $res;

	/**
	 * @param SkinMirage $skin
	 * @param LinkRenderer $linkRenderer
	 * @param ILoadBalancer $loadBalancer
	 * @param SpecialPageFactory $specialPageFactory
	 * @param int[] $contentNamespaces
	 */
	public function __construct(
		SkinMirage $skin,
		LinkRenderer $linkRenderer,
		ILoadBalancer $loadBalancer,
		SpecialPageFactory $specialPageFactory,
		array $contentNamespaces
) {
		parent::__construct( $skin, 'recentchanges' );

		$this->linkRenderer = $linkRenderer;
		$this->specialPageFactory = $specialPageFactory;

		$queryBuilder = $loadBalancer->getConnectionRef( DB_REPLICA )->newSelectQueryBuilder();
		$queryBuilder->select( [
				'rc_actor',
				'rc_namespace',
				'rc_title',
				'rc_timestamp'
			] )
			->from( 'recentchanges' )
			->where( [
				'rc_namespace' => $contentNamespaces,
				'rc_type' => RecentChange::parseToRCType( [ 'new', 'edit' ] ),
				'rc_bot' => 0,
				'rc_deleted' => 0
			] )
			->caller( __METHOD__ )
			->limit( 4 )
			->orderBy( 'rc_timestamp', SelectQueryBuilder::SORT_DESC );

		try {
			$this->res = $queryBuilder->fetchResultSet();
		} catch ( DBError $e ) {
			MWExceptionHandler::logException( $e );
			$this->res = null;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent(): string {
		$html = Html::openElement( 'ul', [ 'class' => 'skin-mirage-unstyled-list' ] );

		foreach ( $this->res as $row ) {
			$html .= Html::rawElement(
				'li',
				[ 'class' => 'skin-mirage-recent-changes-module-rc-row' ],
				$this->renderRecentChange( $row )
			);
		}

		$html .= Html::closeElement( 'ul' );

		$recentChangesLink = $this->linkRenderer->makeKnownLink(
			new TitleValue(
				NS_SPECIAL,
				$this->specialPageFactory->getLocalNameFor( 'Recentchanges' )
			),
			$this->msg( 'mirage-more-recent-changes' )->text()
		);

		return $html . Html::rawElement(
			'span',
			[ 'class' => 'skin-mirage-right-rail-module-bottom-link' ],
			$recentChangesLink
		);
	}

	/**
	 * Render a recent change row.
	 *
	 * This would use the RecentChange class, but that class demands additional rows we don't use.
	 * Also, it still ends up effectively the same as this method as most properties have to be
	 * fetched through a public field, rather than accessors.
	 *
	 * @param stdClass $row
	 * @return string HTML
	 */
	private function renderRecentChange( stdClass $row ): string {
		$html = Html::rawElement(
			'div',
			[ 'class' => 'skin-mirage-recent-changes-module-page' ],
			$this->linkRenderer->makeKnownLink(
				new TitleValue( (int)$row->rc_namespace, $row->rc_title )
			)
		);

		$performer = User::newFromActorId( $row->rc_actor );

		if ( $performer->isAnon() ) {
			$linkTarget = new TitleValue(
				NS_SPECIAL,
				$this->specialPageFactory->getLocalNameFor( 'Contributions', $performer->getName() )
			);
		} else {
			$linkTarget = new TitleValue( NS_USER, $performer->getTitleKey() );
		}

		$userLink = $this->linkRenderer->makeLink(
			$linkTarget,
			$performer->getName()
		);

		$html .= Html::rawElement(
			'div',
			[ 'class' => 'skin-mirage-recent-changes-module-performer-and-time' ],
			$userLink . Html::element(
				'span',
				[ 'class' => 'skin-mirage-recent-changes-module-timestamp' ],
				$this->getSkin()->getLanguage()->getHumanTimestamp(
					MWTimestamp::getInstance( $row->rc_timestamp )
				)
			)
		);

		return $html;
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader(): string {
		return $this->msg( 'mirage-recent-changes-module' )->escaped();
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown(): bool {
		return $this->res && $this->res->numRows() > 0;
	}
}
