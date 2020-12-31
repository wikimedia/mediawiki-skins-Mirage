<?php

namespace MediaWiki\Skins\Mirage\Tests;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use function file;
use function is_readable;
use function preg_last_error;
use function preg_match_all;
use function str_replace;
use function strpos;
use function substr_count;
use const FILE_IGNORE_NEW_LINES;
use const PREG_SET_ORDER;

class TemplateProcessor {
	/**
	 * Path to the template directory.
	 *
	 * @var string
	 */
	private $templateDir;

	/**
	 * Lines in the template.
	 *
	 * @var string[]
	 */
	private $lines;

	/**
	 * Recorded errors.
	 *
	 * @var array
	 */
	private $errors;

	/**
	 * @param string $templateDir Path to the template directory
	 * @param string $template Name of the template
	 */
	public function __construct( string $templateDir, string $template ) {
		$this->templateDir = $templateDir;
		$this->lines = file( "$templateDir/$template", FILE_IGNORE_NEW_LINES );
		$this->errors = [];

		if ( !$this->lines ) {
			throw new InvalidArgumentException(
				"Could not read template $template from $templateDir/$template."
			);
		}
	}

	/**
	 * Process the template.
	 *
	 * @return array The encountered errors
	 */
	public function process() : array {
		foreach ( $this->lines as $index => $line ) {
			$this->parseLine( $line, $index + 1 );
		}

		return $this->errors;
	}

	/**
	 * Parse a line of the template.
	 *
	 * @param string $line
	 * @param int $lineNumber Line number for error reporting
	 */
	private function parseLine( string $line, int $lineNumber ) : void {
		$res = preg_match_all(
			'/(\{\{[\{&!#\/]? *)([a-z-_\.]+)( *\}?\}\})/i',
			$line,
			$matches,
			PREG_SET_ORDER
		);

		if ( $res === false ) {
			throw new RuntimeException(
				"Regular expression error when attempting to process line $lineNumber: " .
				preg_last_error()
			);
		}

		if ( $res === 0 ) {
			return;
		}

		foreach ( $matches as list( $wholeMatch, $openTag, $content, $closeTag ) ) {
			$column = strpos( $line, str_replace( '\t', '    ', $wholeMatch ) );

			foreach ( $this->investigateTag( $openTag, $content, $closeTag ) as $error ) {
				$this->addError(
					$lineNumber,
					$column,
					$error
				);
			}
		}
	}

	/**
	 * @param string $openTag
	 * @param string $content
	 * @param string $closeTag
	 * @return Generator
	 */
	private function investigateTag( string $openTag, string $content, string $closeTag ) : Generator {
		if ( strpos( $openTag, '{{{' ) === 0 ) {
			yield 'Unescaped HTML should use the {{& }} tag';
		} elseif ( $openTag === '{{' && strpos( $content, 'html-' ) === 0 ) {
			yield 'Variables that are provided with html must not escape their contents';
		}

		if ( strpos( $openTag, '{{>' ) === 0 && !is_readable( "$this->templateDir/$content.mustache" ) ) {
			yield "Cannot find partial template $content.mustache";
		}

		if ( substr_count( $openTag, ' ' ) !== 1 ) {
			yield 'Tagnames should be preceded by a single space';
		}

		if ( substr_count( $closeTag, ' ' ) !== 1 ) {
			yield 'Tagnames should be followed by a single space';
		}
	}

	/**
	 * Records an error
	 *
	 * @param int $lineNumber
	 * @param int $column
	 * @param string $message
	 */
	private function addError( int $lineNumber, int $column, string $message ) : void {
		$column++;

		$this->errors["$lineNumber:$column"] = [
			'message' => $message,
			'line' => $lineNumber,
			'column' => $column
		];
	}
}
