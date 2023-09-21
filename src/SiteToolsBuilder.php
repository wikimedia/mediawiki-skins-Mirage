<?php

namespace MediaWiki\Skins\Mirage;

use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MediaWiki\Skins\Mirage\Hook\HookRunner;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use UploadBase;
use function array_slice;
use function count;

class SiteToolsBuilder {
	private HookRunner $hookRunner;

	/** @var false|string */
	private $uploadNavigationUrl;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param HookRunner $hookRunner
	 * @param false|string $uploadNavigationUrl
	 */
	public function __construct( HookRunner $hookRunner, $uploadNavigationUrl ) {
		$this->hookRunner = $hookRunner;
		$this->uploadNavigationUrl = $uploadNavigationUrl;
	}

	/**
	 * Build the site tools.
	 *
	 * @param SkinMirage $skin
	 * @return array Mustache parameters for the site tools
	 */
	public function build( SkinMirage $skin ): array {
		$tools = [
			'RecentChanges' => [
				'icon' => 'recentChanges',
				'href' => SpecialPage::getTitleFor( 'Recentchanges' )->getLocalURL(),
				'msg' => 'recentchanges',
				// Required to have the tooltip and accesskey show up.
				'single-id' => 'n-recentchanges'
			],
			'SpecialPages' => [
				'icon' => 'specialPages',
				'href' => SpecialPage::getTitleFor( 'Specialpages' )->getLocalURL(),
				'msg' => 'specialpages',
				// Required to have the tooltip and accesskey show up.
				'single-id' => 't-specialpages'
			],
			'RandomPage' => [
				'icon' => 'die',
				'href' => SpecialPage::getTitleFor( 'Randompage' )->getLocalURL(),
				'msg' => 'randompage',
				// Required to have the tooltip and accesskey show up.
				'single-id' => 'n-randompage'
			]
		] +
			$this->getPrintableVersionLink( $skin->getOutput(), $skin->getTitle() ) +
			$this->getUploadLink( $skin->getAuthority() );

		$this->hookRunner->onMirageBuildSiteTools( $skin->getContext(), $tools );

		$visibleToolsCounter = 0;
		$siteTools = [];

		foreach ( $tools as $key => $tool ) {
			if ( isset( $tool['icon'] ) ) {
				$icon = MirageIcon::medium( $tool['icon'] );
				// Don't pass this to Skin::makeListItem, or it will show up as an attribute.
				unset( $tool['icon'] );

				if ( $visibleToolsCounter === 1 || $visibleToolsCounter === 2 ) {
					$icon->hideLabel();
				}

				$tool['link-class'] = $icon->toClasses();
			}

			$visibleToolsCounter++;

			$siteTools[] = $skin->makeListItem( $key, $tool );
		}

		$mustacheParameters = [
			'array-site-tools' => array_slice( $siteTools, 0, 3 ),
			'site-tools-dropdown' => null
		];

		if ( count( $siteTools ) > 3 ) {
			$mustacheParameters['site-tools-dropdown'] = [
				'array-dropdown-items' => array_slice( $siteTools, 3 ),
				'html-dropdown-icon' => MirageIcon::medium( 'ellipsis' )
					->hideLabel()
					->setContent( $skin->msg( 'mirage-more' )->plain() )
			];
		}

		return $mustacheParameters;
	}

	/**
	 * Create the upload link.
	 * It is only shown when either $wgUploadNavigationUrl is set, or the user is allowed to
	 * upload files.
	 *
	 * @param Authority $authority Authority to who an upload link will be shown
	 * @return array
	 */
	private function getUploadLink( Authority $authority ): array {
		$uploadLink = [
			'icon' => 'upload',
			'msg' => 'upload',
			// Required to have the tooltip and accesskey show up.
			'single-id' => 't-upload'
		];

		if ( $this->uploadNavigationUrl ) {
			$uploadLink['href'] = $this->uploadNavigationUrl;
		} elseif ( UploadBase::isEnabled() && UploadBase::isAllowed( $authority ) ) {
			$uploadLink['href'] = SpecialPage::getTitleFor( 'Upload' )->getLocalURL();
		} else {
			return [];
		}

		return [ 'Upload' => $uploadLink ];
	}

	/**
	 * Create the printable version link, provided the page is printable.
	 *
	 * @param OutputPage $out
	 * @param Title $title
	 * @return array
	 */
	private function getPrintableVersionLink( OutputPage $out, Title $title ): array {
		if ( $out->isPrintable() || ( !$out->isArticle() && !$title->isSpecialPage() ) ) {
			return [];
		}

		return [
			'Print' => [
				'icon' => 'printer',
				'msg' => 'printableversion',
				'href' => 'javascript:print();',
				// Required to have the tooltip and accesskey show up.
				'single-id' => 't-print'
			]
		];
	}
}
