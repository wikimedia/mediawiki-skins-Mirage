<?php

namespace MediaWiki\Skins\Mirage;

use IContextSource;
use Sanitizer;
use TitleFactory;
use function array_map;
use function explode;
use function preg_match;
use function rtrim;
use function strpos;
use function trim;
use function wfMatchesDomainList;
use function wfUrlProtocols;

class MirageNavigationExtractor {
	/**  @var TitleFactory */
	private $titleFactory;

	/** @var IContextSource */
	private $context;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param TitleFactory $titleFactory
	 * @param IContextSource $context
	 */
	public function __construct( TitleFactory $titleFactory, IContextSource $context ) {
		$this->titleFactory = $titleFactory;
		$this->context = $context;
	}

	/**
	 * Extract a navigation definition from the given message.
	 *
	 * @param string $messageContent Message content (typically from MediaWiki:Mirage-navigation)
	 * @return array
	 */
	public function extract( string $messageContent ) : array {
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
	private function parseLine( string $line ) : array {
		if ( strpos( $line, '|' ) === false ) {
			$target = $text = trim( $line, " \t\0\x0B|" );
		} else {
			list( $target, $text ) = array_map( '\trim', explode( '|', $line, 2 ) );
		}

		$attributes = [
			'id' => Sanitizer::escapeIdForAttribute( 'n-' . strtr( $text, ' ', '_' ) )
		];

		$msg = $this->context->msg( $text );
		if ( !$msg->isDisabled() ) {
			$attributes['msg'] = $msg->getKey();
		} else {
			$attributes['text'] = $text;
		}

		if ( preg_match( '/^(?i:' . wfUrlProtocols() . ')/', $target ) ) {
			$config = $this->context->getConfig();
			$attributes['href'] = $target;

			if (
				$config->get( 'NoFollowLinks' ) &&
				!wfMatchesDomainList( $target, $config->get( 'NoFollowDomainExceptions' ) )
			) {
				$attributes['rel'] = 'nofollow';
			}

			if ( $config->get( 'ExternalLinkTarget' ) ) {
				$attributes['target'] = $config->get( 'ExternalLinkTarget' );
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
