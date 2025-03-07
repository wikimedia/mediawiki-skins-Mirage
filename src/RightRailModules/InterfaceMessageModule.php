<?php

namespace MediaWiki\Skins\Mirage\RightRailModules;

use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Skins\Mirage\SkinMirage;
use Wikimedia\Message\MessageSpecifier;

class InterfaceMessageModule extends RightRailModule {
	private LinkRenderer $linkRenderer;

	private PermissionManager $permissionManager;

	private Message $message;

	private ?Message $headerMessage;

	/**
	 * @param SkinMirage $skinMirage
	 * @param LinkRenderer $linkRenderer
	 * @param PermissionManager $permissionManager
	 * @param MessageSpecifier|string|string[] $message
	 * @param string $moduleId
	 * @param MessageSpecifier|string|string[]|null $headerMessage
	 */
	public function __construct(
		SkinMirage $skinMirage,
		LinkRenderer $linkRenderer,
		PermissionManager $permissionManager,
		$message,
		string $moduleId,
		$headerMessage = null
	) {
		parent::__construct( $skinMirage, $moduleId );

		$this->linkRenderer = $linkRenderer;
		$this->permissionManager = $permissionManager;
		$this->message = $this->msg( $message )->inContentLanguage();
		$this->headerMessage = $headerMessage ? $this->msg( $headerMessage ) : null;
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent(): string {
		$content = Html::rawElement(
			'div',
			[],
			$this->message->parseAsBlock()
		);

		$canEditMessage = $this->permissionManager->userCan(
			'edit',
			$this->getSkin()->getUser(),
			$this->message->getTitle()
		);

		if ( $canEditMessage ) {
			$content .= Html::rawElement(
				'span',
				[ 'class' => 'skin-mirage-right-rail-module-bottom-link' ],
				$this->linkRenderer->makeKnownLink(
					$this->message->getTitle(),
					$this->msg( 'mirage-interface-messages-module-edit-this-message' )->plain(),
					[],
					[ 'action' => 'edit' ]
				)
			);
		}

		return $content;
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeader(): ?string {
		return $this->headerMessage ? $this->headerMessage->escaped() : null;
	}

	/**
	 * @inheritDoc
	 */
	public function canBeShown(): bool {
		return !$this->message->isDisabled();
	}

	/**
	 * @inheritDoc
	 */
	protected function getAdditionalModuleClasses(): array {
		$classes = parent::getAdditionalModuleClasses();

		$classes[] = 'skin-mirage-interface-message-module';

		return $classes;
	}
}
