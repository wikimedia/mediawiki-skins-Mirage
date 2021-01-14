<?php

namespace MediaWiki\Skins\Mirage;

use ExtensionRegistry;
use Generator;
use function in_array;

class PersonalToolsBuilder {
	private const ECHO_PERSONAL_TOOLS = [
		'notifications-alert',
		'notifications-notice'
	];

	/** @var array */
	private $personalTools;

	/** @var SkinMirage */
	private $skin;

	/** @var bool */
	private $hasAvatar;

	/**
	 * @param SkinMirage $skin
	 * @param array $personalTools
	 * @param bool $hasAvatar
	 */
	public function __construct(
		SkinMirage $skin,
		array $personalTools,
		bool $hasAvatar
	) {
		$this->skin = $skin;
		$this->personalTools = $personalTools;
		$this->hasAvatar = $hasAvatar;

		// Mirage doesn't use this as a personal tools item.
		unset( $this->personalTools['anonuserpage'] );

		if ( isset( $this->personalTools['userpage'] ) ) {
			$this->personalTools['userpage']['links'][0]['text'] = $skin->msg( 'mirage-userpage' )
				->text();
		}

		if ( isset( $this->personalTools['mytalk'] ) ) {
			$this->personalTools['mytalk']['links'][0]['text'] = $skin->msg( 'mirage-talkpage' )
				->text();
		} elseif ( isset( $this->personalTools['anontalk'] ) ) {
			$this->personalTools['anontalk']['links'][0]['text'] = $skin->msg( 'mirage-talkpage' )
				->text();
		}
	}

	/**
	 * Generates the mustache parameters for the personal tools template.
	 *
	 * @return array
	 */
	public function getMustacheParameters() : array {
		$user = $this->skin->getUser();
		$hasEcho = ExtensionRegistry::getInstance()->isLoaded( 'Echo' );
		$userAvatarIcon = MirageIcon::medium( 'userAvatar' )->toClasses();

		return [
			'has-avatar' => $this->hasAvatar,
			'html-username-icon-classes' => $this->hasAvatar ? null : $userAvatarIcon,
			'is-anon' => $user->isAnon(),
			'username' => $user->getName(),
			'array-personal-tools' => $this->personalTools ? $this->generatePersonalTools() : null,
			'is-echo' => $hasEcho,
			'html-notifications-icon' => $hasEcho ? null : $this->buildIcon(),
			'array-echo-icons' => $hasEcho ? $this->generateEchoItems() : null
		];
	}

	/**
	 * Builds the notifications icon to show when Echo is not installed.
	 *
	 * @return MirageIcon
	 */
	private function buildIcon() : MirageIcon {
		return MirageIcon::medium( 'bell' )
			->setContent( $this->skin->msg( 'mirage-notifications' )->plain() )
			->hideLabel()
			->setClasses(
				'skin-mirage-talk-page-icon-link',
				$this->skin->getNewtalks() ? 'skin-mirage-user-has-messages' : ''
			);
	}

	/**
	 * Generates the personal tool entries for Echo.
	 *
	 * @return Generator
	 */
	private function generateEchoItems() : Generator {
		foreach ( self::ECHO_PERSONAL_TOOLS as $echoTool ) {
			if ( isset( $this->personalTools[$echoTool] ) ) {
				yield $this->skin->makeListItem(
					$echoTool,
					$this->personalTools[$echoTool]
				);
			}
		}
	}

	/**
	 * Generates a list of personal tools, ready to be used in a Mustache template.
	 *
	 * @return Generator
	 */
	private function generatePersonalTools() : Generator {
		$user = $this->skin->getUser();

		if ( !$user->isAnon() ) {
			yield $this->skin->makeListItem(
				'username',
				[
					'class' => 'skin-mirage-dropdown-username',
					'text' => $user->getName()
				],
				[ 'link-fallback' => 'span' ]
			);
		}

		foreach ( $this->personalTools as $key => $personalTool ) {
			if ( in_array( $key, self::ECHO_PERSONAL_TOOLS, true ) ) {
				continue;
			}

			yield $this->skin->makeListItem(
				$key,
				$personalTool,
				$this->getPersonalToolsIcon( $key )
			);
		}
	}

	/**
	 * Determine the icon for the personal tool, if any.
	 *
	 * @param string $personalTool Name of the personal tool
	 * @return array
	 */
	private function getPersonalToolsIcon( string $personalTool ) : array {
		switch ( $personalTool ) {
			case 'userpage':
				$icon = 'userAvatar';
				break;
			case 'anontalk':
			case 'mytalk':
				$icon = 'userTalk';
				break;
			case 'preferences':
				$icon = 'settings';
				break;
			case 'watchlist':
				// TODO: This would be better with an icon like userContributions, but with
				// unStar in place of the userAvatar
				$icon = 'unStar';
				break;
			// Yes, this is actually how it is spelled. See SkinTemplate::buildPersonalUrls.
			case 'mycontris':
			case 'anoncontribs':
				$icon = 'userContributions';
				break;
			case 'createaccount':
				$icon = 'userAdd';
				break;
			case 'logout':
				$icon = 'logOut';
				break;
			case 'login':
			case 'login-private':
				$icon = 'logIn';
				break;
			default:
				return [];
		}

		return [
			'link-class' => MirageIcon::medium( $icon )->toClasses()
		];
	}
}
