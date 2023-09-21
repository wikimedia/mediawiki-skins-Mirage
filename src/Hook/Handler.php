<?php

namespace MediaWiki\Skins\Mirage\Hook;

use Content;
use ExtensionRegistry;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Hook\AlternateEditPreviewHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Html\Html;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\ImagePageAfterImageLinksHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\Module as ResourceLoaderModule;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Skins\Mirage\Avatars\AvatarLookup;
use MediaWiki\Skins\Mirage\Avatars\NullAvatarLookup;
use MediaWiki\Skins\Mirage\MirageIndicator;
use MediaWiki\Skins\Mirage\MirageNavigationExtractor;
use MediaWiki\Skins\Mirage\MirageWordmarkLookup;
use MediaWiki\Skins\Mirage\ResourceLoader\MirageAvatarResourceLoaderModule;
use MediaWiki\Skins\Mirage\SkinMirage;
use MediaWiki\Skins\Mirage\ThemeRegistry;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\User;
use MediaWiki\User\UserOptionsLookup;
use MediaWiki\Utils\UrlUtils;
use ParserOutput;
use Skin;
use WikitextContent;
use function array_keys;
use function array_search;
use function array_slice;
use const NS_MEDIAWIKI;

class Handler implements
	AlternateEditPreviewHook,
	BeforePageDisplayHook,
	GetPreferencesHook,
	ImagePageAfterImageLinksHook,
	MirageGetExtraIconsHook,
	OutputPageBodyAttributesHook,
	ResourceLoaderRegisterModulesHook
{
	public const MIRAGE_MAX_WIDTH = 0;
	public const MIRAGE_PARTIAL_MAX_WIDTH = 1;
	public const MIRAGE_NO_MAX_WIDTH = 2;

	public const MIRAGE_TOC_LEGACY = 0;
	public const MIRAGE_TOC_HYBRID = 1;
	public const MIRAGE_TOC_RIGHT_RAIL_ONLY = 2;

	private TitleFactory $titleFactory;

	private UserOptionsLookup $optionsLookup;

	private AvatarLookup $avatarLookup;

	private UrlUtils $urlUtils;

	private MirageWordmarkLookup $wordmarkLookup;

	private ?ExtensionRegistry $extensionRegistry;

	private Config $config;

	private bool $useInstantCommons;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param TitleFactory $titleFactory
	 * @param UserOptionsLookup $optionsLookup
	 * @param AvatarLookup $avatarLookup
	 * @param UrlUtils $urlUtils
	 * @param MirageWordmarkLookup $wordmarkLookup
	 * @param ConfigFactory $configFactory
	 * @param ExtensionRegistry|null $extensionRegistry Optional for injection
	 */
	public function __construct(
		TitleFactory $titleFactory,
		UserOptionsLookup $optionsLookup,
		AvatarLookup $avatarLookup,
		UrlUtils $urlUtils,
		MirageWordmarkLookup $wordmarkLookup,
		ConfigFactory $configFactory,
		?ExtensionRegistry $extensionRegistry = null
	) {
		$this->titleFactory = $titleFactory;
		$this->optionsLookup = $optionsLookup;
		$this->avatarLookup = $avatarLookup;
		$this->urlUtils = $urlUtils;
		$this->wordmarkLookup = $wordmarkLookup;
		$this->extensionRegistry = $extensionRegistry ?? ExtensionRegistry::getInstance();
		$this->config = $configFactory->makeConfig( 'Mirage' );
		$this->useInstantCommons = $configFactory->makeConfig( 'main' )
			->get( MainConfigNames::UseInstantCommons );
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
			!$editPage->getTitle()->isSameLinkAs( new TitleValue( NS_MEDIAWIKI, 'Mirage-navigation' ) )
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
			$this->urlUtils,
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
			'html-user-language-attributes' => Html::expandAttributes( [
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
			'mirage-toc' => [
				'type' => 'radio',
				'options-messages' => [
					'prefs-mirage-toc-legacy' => self::MIRAGE_TOC_LEGACY,
					'prefs-mirage-toc-hybrid' => self::MIRAGE_TOC_HYBRID,
					'prefs-mirage-toc-right-rail-only' => self::MIRAGE_TOC_RIGHT_RAIL_ONLY,
				],
				'label-message' => 'prefs-mirage-toc',
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

		$wordmarkFile = $this->wordmarkLookup->getWordmarkFile();

		if ( $wordmarkFile && $imagePage->getTitle()->equals( $wordmarkFile->getTitle() ) ) {
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
		if ( $this->extensionRegistry->isLoaded( 'WikiLove' ) ) {
			$icons['heart'] = [
				'selectorWithVariant' => [
					'destructive' => '#ca-wikilove.icon a::before'
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
		if ( $this->extensionRegistry->isLoaded( 'Theme' ) ) {
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
}
