<?php

namespace MediaWiki\Skins\Mirage\Hook;

use Config;
use ConfigFactory;
use Content;
use EditPage;
use ExtensionRegistry;
use Html;
use MediaWiki\Hook\AlternateEditPreviewHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Hook\PersonalUrlsHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Page\Hook\ImagePageAfterImageLinksHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\MirageIcon;
use MediaWiki\Skins\Mirage\MirageIndicator;
use MediaWiki\Skins\Mirage\MirageNavigationExtractor;
use MediaWiki\Skins\Mirage\ResourceLoader\MirageAvatarResourceLoaderModule;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\Skins\Mirage\ThemeRegistry;
use MediaWiki\User\UserOptionsLookup;
use OutputPage;
use ParserOutput;
use ResourceLoader;
use ResourceLoaderModule;
use Skin;
use SkinTemplate;
use Title;
use TitleFactory;
use TitleValue;
use User;
use WikitextContent;
use Xml;
use function array_key_first;
use function array_keys;
use function array_search;
use function array_slice;
use function is_string;
use const NS_FILE;
use const NS_MEDIAWIKI;

class Handler implements
	AlternateEditPreviewHook,
	BeforePageDisplayHook,
	GetPreferencesHook,
	ImagePageAfterImageLinksHook,
	MirageGetExtraIconsHook,
	OutputPageBodyAttributesHook,
	PersonalUrlsHook,
	ResourceLoaderRegisterModulesHook,
	SkinTemplateNavigation__UniversalHook
{
	public const MIRAGE_MAX_WIDTH = 0;
	public const MIRAGE_PARTIAL_MAX_WIDTH = 1;
	public const MIRAGE_NO_MAX_WIDTH = 2;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var UserOptionsLookup */
	private $optionsLookup;

	/** @var AvatarLookup */
	private $avatarLookup;

	/** @var Config */
	private $config;

	/** @var bool */
	private $useInstantCommons;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param TitleFactory $titleFactory
	 * @param UserOptionsLookup $optionsLookup
	 * @param AvatarLookup $avatarLookup
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		TitleFactory $titleFactory,
		UserOptionsLookup $optionsLookup,
		AvatarLookup $avatarLookup,
		ConfigFactory $configFactory
	) {
		$this->titleFactory = $titleFactory;
		$this->optionsLookup = $optionsLookup;
		$this->avatarLookup = $avatarLookup;
		$this->config = $configFactory->makeConfig( 'Mirage' );
		$this->useInstantCommons = $configFactory->makeConfig( 'main' )->get( 'UseInstantCommons' );
	}

	/**
	 * @inheritDoc
	 *
	 * @param EditPage $editPage
	 * @param Content &$content
	 * @param string &$previewHTML
	 * @param ParserOutput &$parserOutput
	 * @return bool
	 */
	public function onAlternateEditPreview(
		$editPage,
		&$content,
		&$previewHTML,
		&$parserOutput
	): bool {
		$context = $editPage->getContext();
		$skin = $context->getSkin();
		$out = $context->getOutput();

		if (
			!( $skin instanceof SkinMirage ) ||
			!( $content instanceof WikitextContent ) ||
			!$editPage->getTitle()->equals( new TitleValue( NS_MEDIAWIKI, 'Mirage-navigation' ) )
		) {
			return true;
		}

		$pageText = trim( $content->getText() );

		// Don't try and render a preview when disabling, just render the regular page.
		if ( $pageText === '' || $pageText === '-' ) {
			return true;
		}

		$out->enableOOUI();

		if ( $editPage->isConflict ) {
			$conflict = Html::rawElement(
				'div',
				[
					'id' => 'mw-previewconflict',
					'class' => 'warningbox'
				],
				$context->msg( 'previewconflict' )->escaped()
			);
		} else {
			$conflict = '';
		}

		$note = $context->msg( 'previewnote' )->plain() .
			' <span class="mw-continue-editing">' .
			'[[#' . EditPage::EDITFORM_ID . '|' .
			$context->getLanguage()->getArrow() . ' ' .
			$context->msg( 'continue-editing' )->text() . ']]</span>';

		$previewhead = Html::rawElement(
			'div',
			[ 'class' => 'previewnote' ],
			Html::rawElement(
				'h2',
				[ 'id' => 'mw-previewheader' ],
				$context->msg( 'preview' )->escaped()
			) .
			Html::rawElement(
				'div',
				[ 'class' => 'warningbox' ],
				$out->parseAsInterface( $note )
			) . $conflict
		);

		$navigationExtractor = new MirageNavigationExtractor(
			$this->titleFactory,
			$context
		);

		$templateParameters = [
			'array-navigation-modules' => $skin->buildNavigationParameters(
				$navigationExtractor->extract( $pageText )
			),
			'html-dropdown-indicator' => ( new MirageIndicator( 'down' ) )
				->setClasses( 'skin-mirage-dropdown-indicator' ),
			// SkinTemplate::prepareUserLanguageAttributes is protected and final,
			// so just fill in the user language code and direction unconditionally.
			'html-user-language-attributes' => Xml::expandAttributes( [
				'lang' => $context->getLanguage()->getHtmlCode(),
				'dir' => $context->getLanguage()->getDir()
			] )
		];

		$previewHTML = $previewhead . $skin->getTemplateParser()->processTemplate(
			'SiteNavigationPreview',
			$templateParameters
		);

		return false;
	}

	/**
	 * @inheritDoc
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( !( $skin instanceof SkinMirage ) || $this->avatarLookup instanceof NullAvatarLookup ) {
			return;
		}

		$out->addModuleStyles( 'skins.mirage.avatars.styles' );
	}

	/**
	 * @inheritDoc
	 *
	 * @param User $user
	 * @param array $preferences
	 */
	public function onGetPreferences( $user, &$preferences ): void {
		$miragePreferences = [
			'mirage-max-width' => [
				'type' => 'radio',
				'options-messages' => [
					'prefs-mirage-max-width' => self::MIRAGE_MAX_WIDTH,
					'prefs-mirage-partial-max-width' => self::MIRAGE_PARTIAL_MAX_WIDTH,
					'prefs-mirage-no-max-width' => self::MIRAGE_NO_MAX_WIDTH,
				],
				'label-message' => 'prefs-mirage-max-width-label',
				'section' => 'rendering/skin/skin-prefs',
				'hide-if' => [ '!==', 'skin', 'mirage' ]
			],
			'mirage-show-right-rail' => [
				'type' => 'check',
				'label-message' => 'prefs-mirage-show-right-rail',
				'section' => 'rendering/skin/skin-prefs',
				'hide-if' => [ '!==', 'skin', 'mirage' ]
			]
		];

		// Find the skin preference section to add the Mirage preference below it.
		// This pattern is used in both Vector and Popups (T246162).
		$skinSectionIndex = array_search( 'skin', array_keys( $preferences ), true );
		if ( $skinSectionIndex !== false ) {
			$mirageSectionIndex = $skinSectionIndex + 1;
			$preferences = array_slice( $preferences, 0, $mirageSectionIndex, true )
				 + $miragePreferences
				 + array_slice( $preferences, $mirageSectionIndex, null, true );
		} else {
			$preferences += $miragePreferences;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onImagePageAfterImageLinks( $imagePage, &$html ): void {
		if ( !$this->config->get( 'MirageEnableImageWordmark' ) ) {
			return;
		}

		$wordmarkTitle = $this->titleFactory->makeTitle( NS_FILE, 'Mirage-wordmark.png' );

		if ( $imagePage->getTitle()->equals( $wordmarkTitle ) ) {
			$html .= Html::warningBox(
				$imagePage->getContext()->msg( 'mirage-wordmark-file-warning' )->escaped()
			);
		}
	}

	/**
	 * @inheritDoc
	 *
	 * Ideally, WikiLove would implement this hook itself.
	 *
	 * @param array $icons
	 */
	public function onMirageGetExtraIcons( array &$icons ): void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikiLove' ) ) {
			$icons['heart'] = [
				'selectorWithVariant' => [
					'destructive' => '#ca-wikilove.icon a:before'
				],
				'variants' => [
					'destructive'
				]
			];
		}

		if ( $this->useInstantCommons ) {
			$icons['logoWikimediaCommons'] = [];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Theme' ) ) {
			$rl->register(
				( new ThemeRegistry( $this->config ) )->buildResourceLoaderModuleDefinitions()
			);
		}

		if ( $this->avatarLookup instanceof NullAvatarLookup ) {
			return;
		}

		$rl->register( 'skins.mirage.avatars.styles', [
			'targets' => [
				'mobile',
				'desktop'
			],
			'styles' => [
				'skins.mirage.avatars.styles/avatars.less' => [
					'media' => 'screen'
				]
			],
			'origin' => ResourceLoaderModule::ORIGIN_CORE_INDIVIDUAL,
			'factory' => function ( array $options ): MirageAvatarResourceLoaderModule {
				return new MirageAvatarResourceLoaderModule(
					$options,
					null,
					null,
					$this->avatarLookup
				);
			},
			'localBasePath' => __DIR__ . '/../../resources',
			'remoteExtPath' => 'Mirage/resources'
		] );
	}

	/**
	 * @inheritDoc
	 *
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param string[] &$bodyAttrs
	 */
	public function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ): void {
		if ( !( $sk instanceof SkinMirage ) ) {
			return;
		}

		switch ( $this->optionsLookup->getIntOption( $sk->getUser(), 'mirage-max-width' ) ) {
			case self::MIRAGE_NO_MAX_WIDTH:
				return;
			case self::MIRAGE_MAX_WIDTH:
				$bodyAttrs['class'] .= ' skin-mirage-limit-content-width';
				break;
			case self::MIRAGE_PARTIAL_MAX_WIDTH:
			default:
				$bodyAttrs['class'] .= ' skin-mirage-limit-content-width-selectively';
				break;
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @param array &$personal_urls
	 * @param Title &$title
	 * @param SkinTemplate $skin
	 */
	public function onPersonalUrls( &$personal_urls, &$title, $skin ): void {
		if ( !( $skin instanceof SkinMirage ) ) {
			return;
		}

		// Mirage doesn't use this as a personal tools item.
		unset( $personal_urls['anonuserpage'] );

		if ( isset( $personal_urls['userpage'] ) ) {
			$personal_urls['userpage']['text'] = $skin->msg( 'mirage-userpage' )->text();
		}

		if ( isset( $personal_urls['mytalk'] ) ) {
			$personal_urls['mytalk']['text'] = $skin->msg( 'mirage-talkpage' )->text();
		} elseif ( isset( $personal_urls['anontalk'] ) ) {
			$personal_urls['anontalk']['text'] = $skin->msg( 'mirage-talkpage' )->text();
		}

		foreach ( $personal_urls as $key => $personalUrl ) {
			$icon = MirageIcon::medium(
				$personalUrl['icon'] ?? MirageIcon::ICON_PLACEHOLDER
			);

			// Set icon classes on the link, not the <li>.
			if ( is_array( $personalUrl['link-class'] ?? [] ) ) {
				$personal_urls[$key]['link-class'][] = $icon->toClasses();
			} else {
				$personal_urls[$key]['link-class'] .= ' ' . $icon->toClasses();
			}
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @suppress PhanTypeInvalidRightOperand, PhanTypePossiblyInvalidDimOffset, PhanTypeInvalidRightOperandOfAdd
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array[][] &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( !( $sktemplate instanceof SkinMirage ) ) {
			return;
		}

		$links['mirage-edit-button'] = [];
		$links['mirage-edit-button-dropdown'] = [];

		list( 'views' => $views, 'actions' => $actions ) = $links;

		if ( isset( $views['addsection'] ) ) {
			$views['addsection']['label'] = $sktemplate->msg( 'mirage-action-addsection' )->plain();

			$links['mirage-edit-button']['addsection'] = $this->addLinkClass(
				$views['addsection'],
				MirageIcon::medium( $this->findRelevantIcon( 'addsection' ) )
					->setVariant( 'invert' )
					->toClasses()
			);

			// Move the edit button for the whole talk page to the dropdown.
			$links['mirage-edit-button-dropdown']['edit'] = $this->addLinkClass(
				$views['edit'],
				MirageIcon::medium( $this->findRelevantIcon( 'edit' ) )
					->setVariant( 'invert' )
					->toClasses()
			);
		} elseif ( isset( $views['edit'] ) || isset( $views['viewsource'] ) ) {
			$key = isset( $views['edit'] ) ? 'edit' : 'viewsource';

			$links['mirage-edit-button'][$key] = $this->addLinkClass(
				$views[$key],
				MirageIcon::medium( $this->findRelevantIcon( $key ) )
					->setVariant( 'invert' )
					->toClasses()
			);
		}

		if ( isset( $actions['watch'] ) || isset( $actions['unwatch'] ) ) {
			$key = isset( $actions['watch'] ) ? 'watch' : 'unwatch';

			$links['mirage-edit-button'][$key] = $this->addLinkClass(
				$actions[$key],
				MirageIcon::medium( MirageIcon::ICON_PLACEHOLDER )
					->hideLabel()
					->toClasses()
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

		$dropdownItems = $views + $actions;

		// If there is no edit link (or equivalent) and there are dropdown items,
		// pick the first dropdown item to display in the place of the edit link.
		// A watch star might be shown so checking if $links['mirage-edit-button'] won't work.
		if (
			!isset( $links['mirage-edit-button']['addsection'] ) &&
			!isset( $links['mirage-edit-button']['edit'] ) &&
			!isset( $links['mirage-edit-button']['viewsource'] ) &&
			$dropdownItems
		) {
			$key = array_key_first( $dropdownItems );
			$item = $dropdownItems[$key];
			unset( $dropdownItems[$key] );

			$links['mirage-edit-button'] = [
				$key => $this->addLinkClass(
					$item,
					MirageIcon::medium( $this->findRelevantIcon( $key ) )
						->setVariant( 'invert' )
						->toClasses()
				)
			] + $links['mirage-edit-button'];
		}

		foreach ( $dropdownItems as $key => $item ) {
			// Set link-class to apply the 'new' css class to the link.
			// This ensures redlinked pages will be styled properly.
			if ( isset( $item['exists'] ) && $item['exists'] === false ) {
				$item['link-class'] .= 'new';
			}

			$links['mirage-edit-button-dropdown'][$key] = $this->addLinkClass(
				$item,
				MirageIcon::medium( $this->findRelevantIcon( $key ) )->toClasses()
			);
		}
	}

	/**
	 * Find the relevant icon for the given content navigation item.
	 *
	 * @param string $name
	 * @return string OOUI icon name.
	 */
	private function findRelevantIcon( string $name ): string {
		switch ( $name ) {
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
				return 'restore';
			case 'protect':
				return 'lock';
			case 'unprotect':
				return 'unLock';
			case 'view-foreign':
				// Use the Wikimedia Commons logo when InstantCommons is enabled.
				return $this->useInstantCommons ? 'logoWikimediaCommons' : 'newWindow';
			// TODO: Icon needed
			case 'move':
			default:
				return MirageIcon::ICON_PLACEHOLDER;
		}
	}

	/**
	 * Helper to add CSS link-classes.
	 *
	 * @param array $definition
	 * @param string $newClass
	 * @return array
	 */
	private function addLinkClass( array $definition, string $newClass ): array {
		if ( isset( $definition['link-class'] ) ) {
			if ( is_string( $definition['link-class'] ) ) {
				$definition['link-class'] .= ' ' . $newClass;
			} else {
				$definition['link-class'][] = $newClass;
			}
		} else {
			$definition['link-class'] = $newClass;
		}

		return $definition;
	}
}
