<?php

namespace MediaWiki\Skins\Mirage;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MainConfigNames;
use MediaWiki\Message\Message;
use MediaWiki\Skins\Mirage\Hook\HookRunner as MirageHookRunner;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;
use MessageCache;
use Wikimedia\ObjectCache\WANObjectCache;
use function array_diff_key;

class SidebarParser {
	private WANObjectCache $WANObjectCache;

	private MessageCache $messageCache;

	private HookContainer $hookContainer;

	private MirageNavigationExtractor $navigationExtractor;

	private SkinMirage $skin;

	private array $navigationPortals;

	private array $extensionPortals;

	private array $toolsPortal;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param WANObjectCache $WANObjectCache
	 * @param MessageCache $messageCache
	 * @param HookContainer $hookContainer
	 * @param TitleFactory $titleFactory
	 * @param UrlUtils $urlUtils
	 * @param SkinMirage $skin
	 */
	public function __construct(
		WANObjectCache $WANObjectCache,
		MessageCache $messageCache,
		HookContainer $hookContainer,
		TitleFactory $titleFactory,
		UrlUtils $urlUtils,
		SkinMirage $skin
	) {
		$this->WANObjectCache = $WANObjectCache;
		$this->messageCache = $messageCache;
		$this->hookContainer = $hookContainer;
		$this->navigationExtractor = new MirageNavigationExtractor(
			$titleFactory,
			$urlUtils,
			$skin->getContext()
		);
		$this->skin = $skin;
		$this->navigationPortals = [];
		$this->extensionPortals = [];
		$this->toolsPortal = [];
	}

	/**
	 * Parse the sidebar into distinct parts.
	 */
	public function parse(): void {
		$sidebar = $this->skin->buildSidebar();

		// Stub this for extensions expecting them to exist.
		$diffBar = [
			'LANGUAGES' => [],
			'SEARCH' => [],
			'TOOLBOX' => [],
		];

		$hookRunner = new HookRunner( $this->hookContainer );
		$hookRunner->onSkinBuildSidebar( $this->skin, $diffBar );
		$hookRunner->onSidebarBeforeOutput( $this->skin, $diffBar );

		// MediaWiki:Sidebar can contain optional 'magic words'.
		// Mirage gives these modules a predefined spot.
		$this->toolsPortal = $sidebar['TOOLBOX'];
		unset(
			$sidebar['LANGUAGES'],
			$sidebar['SEARCH'],
			$sidebar['TOOLBOX'],
			$diffBar['LANGUAGES'],
			$diffBar['SEARCH'],
			$diffBar['TOOLBOX']
		);

		$this->extensionPortals = $diffBar;

		$msg = $this->skin->msg( 'mirage-navigation' )->inContentLanguage();
		if ( $msg->isDisabled() ) {
			$this->navigationPortals = array_diff_key( $sidebar, $diffBar );
		} else {
			$this->navigationPortals = $this->extractMirageNavigation( $msg );
		}
	}

	/**
	 * Extracts the navigation from the content of MediaWiki:Mirage-navigation.
	 *
	 * @param Message $msg
	 * @return array
	 */
	private function extractMirageNavigation( Message $msg ): array {
		/**
		 * @param null $old
		 * @param int|null $ttl
		 * @return array
		 */
		$callback = function ( $old = null, ?int &$ttl = null ) use ( $msg ): array {
			$bar = $this->navigationExtractor->extract( $msg->plain() );
			( new MirageHookRunner( $this->hookContainer ) )->onMirageBuildNavigation(
				$this->skin,
				$bar
			);
			if ( $this->messageCache->isDisabled() ) {
				// bug T133069
				$ttl = WANObjectCache::TTL_UNCACHEABLE;
			}

			return $bar;
		};
		$config = $this->skin->getConfig();
		$languageCode = $this->skin->getLanguage()->getCode();

		if ( $config->get( MainConfigNames::EnableSidebarCache ) ) {
			return $this->WANObjectCache->getWithSetCallback(
				$this->WANObjectCache->makeKey( 'mirage-sidebar', $languageCode ),
				$config->get( MainConfigNames::SidebarCacheExpiry ),
				$callback,
				[
					'checkKeys' => [
						// Unless there is both no exact $code override nor an i18n definition
						// in the software, the only MediaWiki page to check is for $code.
						$this->messageCache->getCheckKey( $languageCode )
					],
					'lockTSE' => 30
				]
			);
		} else {
			return $callback();
		}
	}

	/**
	 * Return the navigation portals defined in MediaWiki:Sidebar or MediaWiki:Mirage-navigation.
	 *
	 * @return array
	 */
	public function getNavigationPortals(): array {
		return $this->navigationPortals;
	}

	/**
	 * Return the portals added by extensions.
	 *
	 * @return array
	 */
	public function getExtensionPortals(): array {
		return $this->extensionPortals;
	}

	/**
	 * Return the tools portal.
	 *
	 * @return array
	 */
	public function getToolsPortal(): array {
		return $this->toolsPortal;
	}
}
