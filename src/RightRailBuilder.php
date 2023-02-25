<?php

namespace MediaWiki\Skins\Mirage;

use MediaWiki\MainConfigNames;
use MediaWiki\Skins\Mirage\Hook\Handler;
use MediaWiki\Skins\Mirage\Hook\HookRunner;
use MediaWiki\Skins\Mirage\RightRailModules\CategoriesModule;
use MediaWiki\Skins\Mirage\RightRailModules\GenericItemListModule;
use MediaWiki\Skins\Mirage\RightRailModules\InterfaceMessageModule;
use MediaWiki\Skins\Mirage\RightRailModules\PageToolsModule;
use MediaWiki\Skins\Mirage\RightRailModules\RecentChangesModule;
use MediaWiki\Skins\Mirage\RightRailModules\RightRailModule;
use MediaWiki\Skins\Mirage\RightRailModules\TableOfContentsModule;
use MediaWiki\Skins\Mirage\RightRailModules\UserToolsModule;
use MediaWiki\User\UserOptionsLookup;
use Wikimedia\ObjectFactory\ObjectFactory;
use function array_diff_key;
use function array_flip;

class RightRailBuilder {
	private ObjectFactory $objectFactory;

	private HookRunner $hookRunner;

	private UserOptionsLookup $optionsLookup;

	private SidebarParser $sidebarParser;

	private SkinMirage $skin;

	/** @var string[] */
	private array $hiddenRightRailModules;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param ObjectFactory $objectFactory
	 * @param HookRunner $hookRunner
	 * @param UserOptionsLookup $optionsLookup
	 * @param SidebarParser $sidebarParser
	 * @param SkinMirage $skin
	 * @param string[] $hiddenRightRailModules
	 */
	public function __construct(
		ObjectFactory $objectFactory,
		HookRunner $hookRunner,
		UserOptionsLookup $optionsLookup,
		SidebarParser $sidebarParser,
		SkinMirage $skin,
		array $hiddenRightRailModules
	) {
		$this->objectFactory = $objectFactory;
		$this->hookRunner = $hookRunner;
		$this->optionsLookup = $optionsLookup;
		$this->sidebarParser = $sidebarParser;
		$this->skin = $skin;
		$this->hiddenRightRailModules = $hiddenRightRailModules;
	}

	/**
	 * Get the right rail modules to display.
	 *
	 * @return array[]|null
	 */
	public function buildModules(): ?array {
		$modules = [];

		foreach ( $this->determineModules() as $spec ) {
			/** @var RightRailModule $module */
			$module = $this->objectFactory->createObject(
				$spec,
				[
					'assertClass' => RightRailModule::class,
					'extraArgs' => [
						$this->skin
					]
				]
			);

			if ( !$module->canBeShown() ) {
				continue;
			}

			$modules[] = $module->getTemplateParameters();
		}

		if ( !$modules ) {
			return null;
		}

		return [
			'array-right-rail-modules' => $modules
		];
	}

	/**
	 * Determine which modules should be shown.
	 *
	 * @return array[]
	 */
	private function determineModules(): array {
		$modules = [];

		if (
			$this->optionsLookup->getOption( $this->skin->getUser(), 'mirage-toc' ) !== Handler::MIRAGE_TOC_LEGACY &&
			$this->skin->getOutput()->isTOCEnabled()
		) {
			$modules['TableOfContents'] = [
				'class' => TableOfContentsModule::class,
				'args' => [
					$this->skin->getOutput()->getTOCData(),
					$this->skin->getConfig()->get( MainConfigNames::MaxTocLevel )
				]
			];
		}

		if (
			!$this->skin->getUser()->isAnon() &&
			$this->skin->getTitle()->isContentPage()
		) {
			$modules['RecentChanges'] = [
				'class' => RecentChangesModule::class,
				'services' => [
					'LinkRenderer',
					'DBLoadBalancer',
					'SpecialPageFactory',
					'UserFactory'
				],
				'args' => [
					$this->skin->getConfig()->get( MainConfigNames::ContentNamespaces )
				]
			];
		}

		$modules['PageToolsModule'] = [
			'class' => PageToolsModule::class,
			'args' => [
				$this->sidebarParser->getToolsPortal()
			]
		];

		if ( $this->skin->getRelevantUser() !== null ) {
			$modules['UserToolsModule'] = [
				'class' => UserToolsModule::class,
				'args' => [
					$this->sidebarParser->getToolsPortal()
				]
			];
		}

		if ( !$this->skin->getRelevantTitle()->isSpecialPage() ) {
			$modules['Categories'] = [
				'class' => CategoriesModule::class,
				'services' => [
					'LinkRenderer',
					'TitleFactory',
					'UserOptionsLookup'
				]
			];
		}

		foreach ( $this->sidebarParser->getExtensionPortals() as $name => $portal ) {
			$modules[$name] = [
				'class' => GenericItemListModule::class,
				'args' => [
					$name,
					$portal
				]
			];
		}

		$modules['CommunityMessages'] = [
			'class' => InterfaceMessageModule::class,
			'services' => [
				'LinkRenderer',
				'PermissionManager'
			],
			'args' => [
				'mirage-community-messages',
				'community-messages',
				'mirage-community-messages-header'
			]
		];

		$this->hookRunner->onMirageGetRightRailModules(
			$this->skin->getContext(),
			$modules
		);

		// Skip right rail modules specified in $wgMirageHiddenRightRailModules.
		return array_diff_key( $modules, array_flip( $this->hiddenRightRailModules ) );
	}
}
