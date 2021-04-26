<?php

namespace MediaWiki\Skins\Mirage;

use BagOStuff;
use Config;
use ConfigFactory;
use EmptyBagOStuff;
use Generator;
use Html;
use Language;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\Hook\HookRunner;
use MediaWiki\User\UserOptionsLookup;
use MessageCache;
use Sanitizer;
use SkinMustache;
use TemplateParser;
use Title;
use TitleFactory;
use User;
use WANObjectCache;
use Wikimedia\ObjectFactory\ObjectFactory;
use function array_shift;
use function implode;
use function is_array;

class SkinMirage extends SkinMustache {

	public const TEMPLATE_DIR = __DIR__ . '/../resources/templates';

	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var ObjectFactory */
	private $objectFactory;

	/** @var MirageWordmarkLookup */
	private $wordmarkLookup;

	/** @var AvatarLookup */
	private $avatarLookup;

	/** @var WANObjectCache */
	private $WANObjectCache;

	/** @var MessageCache */
	private $messageCache;

	/** @var HookContainer */
	private $hookContainer;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var TemplateParser */
	private $templateParser;

	/** @var Config */
	private $mirageConfig;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/**
	 * @param LinkRenderer $linkRenderer
	 * @param ObjectFactory $objectFactory
	 * @param BagOStuff $localServerCache
	 * @param MirageWordmarkLookup $wordmarkLookup
	 * @param AvatarLookup $avatarLookup
	 * @param TitleFactory $titleFactory
	 * @param ConfigFactory $configFactory
	 * @param WANObjectCache $WANObjectCache
	 * @param MessageCache $messageCache
	 * @param HookContainer $hookContainer
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param array $options Skin options
	 */
	public function __construct(
		LinkRenderer $linkRenderer,
		ObjectFactory $objectFactory,
		BagOStuff $localServerCache,
		MirageWordmarkLookup $wordmarkLookup,
		AvatarLookup $avatarLookup,
		TitleFactory $titleFactory,
		ConfigFactory $configFactory,
		WANObjectCache $WANObjectCache,
		MessageCache $messageCache,
		HookContainer $hookContainer,
		UserOptionsLookup $userOptionsLookup,
		array $options
	) {
		$options['templatedirectory'] = self::TEMPLATE_DIR;
		parent::__construct( $options );

		$this->linkRenderer = $linkRenderer;
		$this->objectFactory = $objectFactory;
		$this->wordmarkLookup = $wordmarkLookup;
		$this->avatarLookup = $avatarLookup;
		$this->WANObjectCache = $WANObjectCache;
		$this->messageCache = $messageCache;
		$this->hookContainer = $hookContainer;
		$this->titleFactory = $titleFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->mirageConfig = $configFactory->makeConfig( 'Mirage' );

		if ( $this->mirageConfig->get( 'MirageForceTemplateRecompilation' ) ) {
			$cache = new EmptyBagOStuff();
		} else {
			$cache = $localServerCache;
		}

		$this->templateParser = new TemplateParser( self::TEMPLATE_DIR, $cache );
	}

	/**
	 * @inheritDoc
	 *
	 * This method is public to allow hook handlers to re-use the cached templates, without
	 * knowing where the templates are located.
	 *
	 * @return TemplateParser
	 */
	public function getTemplateParser() : TemplateParser {
		return $this->templateParser;
	}

	/**
	 * @inheritDoc
	 */
	protected function getHookContainer() : HookContainer {
		return $this->hookContainer;
	}

	/**
	 * Adjusted variant of @see Skin::doEditSectionLink(), that doesn't include those pesky
	 * brackets.
	 *
	 * It also addresses the RTL in LTR text (and vice versa) issue of its parent.
	 *
	 * @param Title $nt The title being linked to (may not be the same as
	 *   the current page, if the section is included from a template)
	 * @param string $section The designation of the section being pointed to,
	 *   to be included in the link, like "&section=$section"
	 * @param string|null $tooltip The tooltip to use for the link: will be escaped
	 *   and wrapped in the 'editsectionhint' message
	 * @param Language $lang Language code
	 * @return string HTML to use for edit link
	 */
	public function doEditSectionLink( Title $nt, $section, $tooltip, Language $lang ) : string {
		$attribs = [
			'class' => MirageIcon::small( 'edit' )->toClasses()
		];
		if ( $tooltip !== null ) {
			$attribs['title'] = $this->msg( 'editsectionhint' )
				->rawParams( $tooltip )
				->inLanguage( $lang )->plain();
		}

		$links = [
			'editsection' => [
				'text' => $this->msg( 'editsection' )->inLanguage( $lang )->plain(),
				'targetTitle' => $nt,
				'attribs' => $attribs,
				'query' => [ 'action' => 'edit', 'section' => $section ]
			]
		];

		$this->getHookRunner()->onSkinEditSectionLinks(
			$this,
			$nt,
			$section,
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$tooltip,
			$links,
			$lang
		);

		$linksHtml = [];
		foreach ( $links as $linkDetails ) {
			$linksHtml[] = $this->linkRenderer->makeKnownLink(
				$linkDetails['targetTitle'],
				$linkDetails['text'],
				$linkDetails['attribs'],
				$linkDetails['query']
			);
		}

		$dividerHtml = Html::element(
			'span',
			[ 'class' => 'mw-editsection-divider' ],
			$this->msg( 'pipe-separator' )->inLanguage( $lang )->plain()
		);

		return Html::rawElement(
			'span',
			[
				'class' => 'mw-editsection',
				'dir' => $lang->getDir()
			],
			implode( $dividerHtml, $linksHtml )
		);
	}

	/**
	 * Build the mustache parameters for the site navigation.
	 *
	 * This method is public to allow the handler for the AlternateEditPreview hook to use it.
	 *
	 * @param array[] $sidebar
	 * @return Generator
	 */
	public function buildNavigationParameters( array $sidebar ) : Generator {
		$indicatorIcon = MirageIcon::small( 'next' )
			->setContent( $this->msg( 'mirage-expand-submenu' )->plain() )
			->hideLabel()
			->setClasses( 'skin-mirage-sub-list-icon' );

		foreach ( $sidebar as $name => $values ) {
			if ( !is_array( $values ) || $values === [] ) {
				continue;
			}

			$msg = $this->msg( $name );

			$navigationEntry = [
				'html-id' => Sanitizer::escapeIdForAttribute( "p-$name" ),
				'header-text' => !$msg->isDisabled() ? $msg->plain() : $name,
				'array-links' => []
			];

			$tooltip = $this->msg( "tooltip-$name" );
			if ( !$tooltip->isDisabled() ) {
				$navigationEntry['html-tooltip'] = $tooltip->escaped();
			}

			foreach ( $values as $key => $value ) {
				$subLinks = $value['links'] ?? [];
				$id = $value['single-id'] = $value['id'];
				// Don't pass these to makeLink.
				unset( $value['links'], $value['id'] );

				$link = [
					'html-id' => $id
				];

				if ( $subLinks ) {
					// When there are sub links, but the item does not have contain a link,
					// set the tabindex. This allows tabbing to it and opening the sub menu.
					if ( !( $value['href'] ?? false ) ) {
						$value['tabindex'] = '0';
					}

					$link['array-sub-links'] = [
						'html-extend-indicator' => $indicatorIcon,
						'array-links' => []
					];

					foreach ( $subLinks as $subLinkKey => $subLink ) {
						$id = $subLink['single-id'] = $subLink['id'];
						unset( $subLink['id'] );

						$link['array-sub-links']['array-links'][] = [
							'html-id' => $id,
							'html-link' => $this->makeLink(
								$subLinkKey,
								$subLink,
								[ 'link-fallback' => 'span' ]
							)
						];
					}
				}

				$link['html-link'] = $this->makeLink(
					$key,
					$value,
					[ 'link-fallback' => 'span' ]
				);

				$navigationEntry['array-links'][] = $link;
			}

			yield $navigationEntry;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateData() : array {
		$out = $this->getOutput();
		$user = $this->getUser();
		$contentNavigation = $this->buildContentNavigationUrls();
		$sidebarParser = new SidebarParser(
			$this->WANObjectCache,
			$this->messageCache,
			$this->getHookContainer(),
			$this->titleFactory,
			$this
		);
		$sidebarParser->parse();
		$rightRailBuilder = new RightRailBuilder(
			$this->objectFactory,
			new HookRunner( $this->getHookContainer() ),
			$sidebarParser,
			$this,
			$this->mirageConfig->get( 'MirageHiddenRightRailModules' )
		);
		$siteToolsBuilder = new SiteToolsBuilder(
			new HookRunner( $this->getHookContainer() ),
			$this->getConfig()->get( 'UploadNavigationUrl' )
		);

		$rightRailModules = $rightRailBuilder->buildModules();
		$rightRailCollapseButton = null;

		if ( $rightRailModules ) {
			$out->addBodyClasses( 'skin-mirage-page-with-right-rail' );
			$rightRailCollapseButton = MirageIcon::medium( 'doubleChevronEnd' )
				->hideLabel()
				->setContent( $this->msg( 'mirage-toggle-right-rail' )->plain() )
				->setClasses( 'mw-checkbox-hack-button' )
				->setElement( 'label' )
				->setAttributes( [
					'id' => 'mirage-right-rail-button',
					'role' => 'button',
					'for' => 'mirage-right-rail-checkbox',
					'tabindex' => 0,
					'aria-controls' => 'mirage-right-rail',
					'title' => $this->msg( 'mirage-toggle-right-rail' )->plain()
				] );
		}

		$hasAvatar = !( $this->avatarLookup instanceof NullAvatarLookup );
		$userAvatarIcon = MirageIcon::medium( 'userAvatar' )->toClasses();

		return [
			'page-langcode' => $this->getTitle()->getPageViewLanguage()->getHtmlCode(),
			'page-isarticle' => (bool)$out->isArticle(),
			'html-right-rail-collapse-button' => $rightRailCollapseButton,
			'is-right-rail-visible' => $this->displayRightRailVisible( $user ),
			'data-header' => [
				'html-dropdown-indicator' => ( new MirageIndicator( 'down' ) )
					->setClasses( 'skin-mirage-dropdown-indicator' ),

				'sitename' => $this->getConfig()->get( 'Sitename' ),
				'has-mirage-wordmark' => $this->wordmarkLookup->getWordmarkUrl() !== null,

				// Personal tools.
				'has-avatar' => $hasAvatar,
				'html-username-icon-classes' => $hasAvatar ? null : $userAvatarIcon,
				'is-anon' => $user->isAnon(),
				'username' => $user->getName(),

				// Notifications.
				'has-notifications' => (bool)$contentNavigation['notifications'],
				'html-notifications-icon' => MirageIcon::medium( 'bell' )
					->setContent( $this->msg( 'mirage-notifications' )->plain() )
					->hideLabel()
					->setClasses(
						'skin-mirage-talk-page-icon-link',
						$this->getNewtalks() ? 'skin-mirage-user-has-messages' : ''
					),

				// Main navigation.
				'array-navigation-modules' => $this->buildNavigationParameters(
					$sidebarParser->getNavigationPortals()
				),

				// Language button.
				'html-language-button-icon' => MirageIcon::medium( 'language' )
					->toClasses(),

				// Edit button.
				'data-page-actions' => $this->getEditButton(
					$contentNavigation['views'],
					$contentNavigation['actions']
				)
			] + $siteToolsBuilder->build( $this ),
			'array-right-rail' => $rightRailModules,
			'array-extra-footer-links' => $this->buildExtraFooterLinks()
		] + $this->adjustSkinMustacheParameters( parent::getTemplateData() );
	}

	/**
	 * Determine if the right rail should be displayed visible or collapsed.
	 *
	 * For anonymous users, this uses $wgMirageRightRailVisibleToAnonByDefault.
	 * For logged-in users, this uses the mirage-show-right-rail preference.
	 *
	 * @param User $user
	 * @return bool
	 */
	private function displayRightRailVisible( User $user ) : bool {
		if ( $user->isAnon() ) {
			return $this->mirageConfig->get( 'MirageRightRailVisibleToAnonByDefault' );
		}

		return $this->userOptionsLookup->getBoolOption(
			$user,
			'mirage-show-right-rail'
		);
	}

	/**
	 * Creates the mustache parameters for the EditButton template, for the edit button.
	 *
	 * @param array $views
	 * @param array $actions
	 * @return array|null
	 */
	private function getEditButton( array $views, array $actions ) : ?array {
		$dropdown = [];
		$editButton = null;

		if ( isset( $views['addsection'] ) ) {
			$views['addsection']['label'] = $this->msg( 'mirage-action-addsection' )->plain();
			$editButton = $this->makeListItem(
				'addsection',
				$views['addsection'],
				[
					'link-class' => MirageIcon::medium( self::findRelevantIcon( 'addsection' ) )
						->setVariant( 'invert' )
						->toClasses()
				]
			);

			// Move the edit button for the whole talk page to the dropdown.
			$dropdown['edit'] = $views['edit'];
		} elseif ( isset( $views['edit'] ) || isset( $views['viewsource'] ) ) {
			$key = isset( $views['edit'] ) ? 'edit' : 'viewsource';

			$editButton = $this->makeListItem(
				$key,
				$views[$key],
				[
					'link-class' => MirageIcon::medium( self::findRelevantIcon( $key ) )
						->setVariant( 'invert' )
						->toClasses()
				]
			);
		}

		$watchButton = null;
		if ( isset( $actions['watch'] ) ) {
			$watchButton = $this->makeListItem(
				'watch',
				$actions['watch'],
				[
					'link-class' => MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
						->hideLabel()
						->toClasses()
				]
			);
		} elseif ( isset( $actions['unwatch'] ) ) {
			$watchButton = $this->makeListItem(
				'unwatch',
				$actions['unwatch'],
				[
					'link-class' => MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
						->hideLabel()
						->toClasses()
				]
			);
		}

		unset(
			$views['view'],
			$views['edit'],
			$views['viewsource'],
			$views['addsection'],
			$actions['watch'],
			$actions['unwatch']
		);

		$dropdownItems = [];

		foreach ( $dropdown + $views + $actions as $key => $item ) {
			// Set link-class to apply the 'new' css class to the link.
			// This ensures redlinked pages will be styled properly.
			if ( isset( $item['exists'] ) && $item['exists'] === false ) {
				$item['link-class'] = 'new';
			}

			$dropdownItems[] = $this->makeListItem(
				$key,
				$item,
				[
					'link-class' => MirageIcon::medium( self::findRelevantIcon( $key ) )
						// Set the inverted icon variant for the first item in the list when there is
						// no edit button, so it can be used as replacement edit button.
						->setVariant( ( !$editButton && !$dropdownItems ) ? 'invert' : '' )
						->toClasses()
				]
			);
		}

		if ( $editButton === null && !$dropdownItems ) {
			return null;
		}

		return [
			'html-edit-button' => $editButton ?? array_shift( $dropdownItems ),
			'html-watch-button' => $watchButton,
			'html-edit-button-dropdown' => $dropdownItems ? [
				'array-dropdown-items' => $dropdownItems,
				'html-dropdown-indicator' => ( new MirageIndicator( 'down' ) )
					->setContent( $this->msg( 'mirage-more' )->plain() )
					->setClasses( 'skin-mirage-dropdown-indicator' )
					->setVariant( 'invert' )
			] : null
		];
	}

	/**
	 * Helper method to prevent polluting getTemplateData with array modifications.
	 *
	 * @param array $parameters
	 * @return array
	 */
	private function adjustSkinMustacheParameters( array $parameters ) : array {
		$parameters['data-footer']['data-places']['label'] = $this->msg( 'mirage-footer-places' )->text();

		// Set the icon to the logo when not defined, to allow displaying something.
		// Prefer svg over 1x to make it look better.
		$parameters['data-logos'] += [
			'icon' => $parameters['data-logos']['svg'] ?? $parameters['data-logos']['1x']
		];

		// Don't ship things that are empty.
		if ( empty( $parameters['data-footer']['data-info']['array-items'] ) ) {
			unset( $parameters['data-footer']['data-info'] );
		}

		return $parameters;
	}

	/**
	 * Builds extra footer links.
	 *
	 * @return array
	 */
	private function buildExtraFooterLinks() : array {
		$footerLinks = [];
		$feeds = $this->buildFeedUrls();

		if ( $feeds ) {
			$items = [];

			foreach ( $feeds as $format => $feed ) {
				$items[] = [
					'id' => "footer-feeds-$format",
					'html' => $this->makeListItem( $format, $feed )
				];
			}

			$footerLinks[] = [
				'id' => 'footer-feeds',
				'label' => $this->msg( 'mirage-footer-feeds' )->text(),
				'array-items' => $items
			];
		}

		$extraFooterLinks = [];
		( new HookRunner( $this->getHookContainer() ) )->onMirageExtraFooterLinks(
			$this,
			$extraFooterLinks
		);

		foreach ( $extraFooterLinks as $category => $links ) {
			if ( !$links ) {
				continue;
			}

			$items = [];

			foreach ( $links as $name => $link ) {
				$items[] = [
					'id' => "footer-$category-$name",
					'html' => $link
				];
			}

			$footerLinks[] = [
				'id' => "footer-$category",
				'label' => $this->msg( $category )->text(),
				'array-items' => $items
			];
		}

		return $footerLinks;
	}

	/**
	 * Find the relevant icon for the given content navigation item.
	 *
	 * @param string $name
	 * @return string OOUI icon name.
	 */
	public static function findRelevantIcon( string $name ) : string {
		switch ( $name ) {
			case 'userpage':
				return 'userAvatar';
			case 'anontalk':
			case 'mytalk':
				return 'userTalk';
			case 'preferences':
				return 'settings';
			case 'watchlist':
				// TODO: This would be better with an icon like userContributions, but with
				// unStar in place of the userAvatar
				return 'unStar';
			// Yes, this is actually how it is spelled. See SkinTemplate::buildPersonalUrls.
			case 'mycontris':
			case 'anoncontribs':
				return 'userContributions';
			case 'createaccount':
				return 'userAdd';
			case 'logout':
				return 'logOut';
			case 'login':
			case 'login-private':
				return 'logIn';
			case 'edit':
			case 'history':
				return $name;
			case 'addsection':
				return 'speechBubbleAdd';
			case 'viewsource':
				return 'editLock';
			case 'delete':
				return 'trash';
			case 'undelete':
				return 'unTrash';
			case 'protect':
				return 'lock';
			case 'unprotect':
				return 'unLock';
			case 'view-foreign':
				return 'newWindow';
			// TODO: Icon needed
			case 'move':
			default:
				return MirageIcon::ICON_PLACEHOLDER;
		}
	}
}
