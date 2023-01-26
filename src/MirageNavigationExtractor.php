<?php

namespace MediaWiki\Skins\Mirage;

use IContextSource;
use MediaWiki\MainConfigNames;
use MediaWiki\Utils\UrlUtils;
use Sanitizer;
use TitleFactory;
use function array_map;
use function explode;
use function preg_match;
use function rtrim;
use function strpos;
use function trim;

class MirageNavigationExtractor {
	private TitleFactory $titleFactory;

	private UrlUtils $urlUtils;

	private IContextSource $context;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param TitleFactory $titleFactory
	 * @param UrlUtils $urlUtils
	 * @param IContextSource $context
	 */
	public function __construct(
		TitleFactory $titleFactory,
		UrlUtils $urlUtils,
		IContextSource $context
	) {
		$this->titleFactory = $titleFactory;
		$this->urlUtils = $urlUtils;
		$this->context = $context;
	}

	/**
	 * Extract a navigation definition from the given message.
	 *
	 * @param string $messageContent Message content (typically from MediaWiki:Mirage-navigation)
	 * @return array
	 */
	public function extract( string $messageContent ): array {
		$messageContent = trim( $messageContent );

		// Don't try to extract from disabled or empty messages.
		if ( $messageContent === '-' || $messageContent === '' ) {
			return [];
		}

		$navigation = [];
		$activeHeading = '';
		$activeSubHeading = '';

		foreach ( explode( "\n", $messageContent ) as $line ) {
			$line = trim( $line );

			// Skip lines with no list item or an empty list item.
			if ( strpos( $line, '*' ) !== 0 || trim( $line, '* ' ) === '' ) {
				continue;
			}

			// For Windows compatibility.
			$line = rtrim( $line, "\r" );

			if ( $line[2] === '*' ) {
				if ( $activeSubHeading === '' ) {
					continue;
				}

				$navigation[$activeHeading][$activeSubHeading]['links'][] = $this->parseLine(
					trim( $line, '* ' )
				);
			} elseif ( $line[1] === '*' ) {
				if ( $activeHeading === '' ) {
					continue;
				}

				$activeSubHeading = trim( $line, '* ' );

				$navigation[$activeHeading][$activeSubHeading] = [
					'links' => []
				] + $this->parseLine( $activeSubHeading );
			} elseif ( $line[0] === '*' ) {
				$activeHeading = trim( $line, '* ' );
				$navigation[$activeHeading] = [];
			}
		}

		return $navigation;
	}

	/**
	 * Parse a line from mediawiki:mirage-navigation and build a link entry from it.
	 *
	 * @param string $line
	 * @return string[]
	 */
	private function parseLine( string $line ): array {
		if ( strpos( $line, '|' ) === false ) {
			$target = $text = trim( $line, " \t\0\x0B|" );
		} else {
			list( $target, $text ) = array_map( '\trim', explode( '|', $line, 2 ) );
		}

		$attributes = [
			'id' => Sanitizer::escapeIdForAttribute( 'n-' . str_replace( ' ', '_', $text ) )
		];

		$msg = $this->context->msg( $text );
		if ( !$msg->isDisabled() ) {
			$attributes['msg'] = $msg->getKey();
		} else {
			$attributes['text'] = $text;
		}

		if ( preg_match( '/^(?i:' . $this->urlUtils->validProtocols() . ')/', $target ) ) {
			$config = $this->context->getConfig();
			$attributes['href'] = $target;

			if (
				$config->get( MainConfigNames::NoFollowLinks ) &&
				!$this->urlUtils->matchesDomainList(
					$target,
					$config->get( MainConfigNames::NoFollowDomainExceptions )
				)
			) {
				$attributes['rel'] = 'nofollow';
			}

			if ( $config->get( MainConfigNames::ExternalLinkTarget ) ) {
				$attributes['target'] = $config->get( MainConfigNames::ExternalLinkTarget );
			}
		} elseif ( $target !== '' ) {
			$title = $this->titleFactory->newFromText( $target );

			if ( $title ) {
				$attributes['href'] = $title->fixSpecialName()->getLocalURL();
			}
		}

		return $attributes;
	}
}
