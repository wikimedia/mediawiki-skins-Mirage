<?php

namespace MediaWiki\Skins\Mirage;

use File;
use MediaWiki\Title\TitleFactory;
use RepoGroup;
use const NS_FILE;

class MirageWordmarkLookup {
	/**
	 * Names of the wordmark files.
	 * Titles are iterated in order: the first file will take precedence if multiple exist.
	 */
	private const WORDMARK_FILE_NAMES = [
		'Mirage-wordmark.svg',
		'Mirage-wordmark.png'
	];

	private TitleFactory $titleFactory;

	private RepoGroup $repoGroup;

	private bool $wordmarkEnabled;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param TitleFactory $titleFactory
	 * @param RepoGroup $repoGroup
	 * @param bool $wordmarkEnabled Value of the $wgMirageWordmark enabled config setting
	 */
	public function __construct(
		TitleFactory $titleFactory,
		RepoGroup $repoGroup,
		bool $wordmarkEnabled
	) {
		$this->titleFactory = $titleFactory;
		$this->repoGroup = $repoGroup;
		$this->wordmarkEnabled = $wordmarkEnabled;
	}

	/**
	 * Returns the path to the wordmark url, if there is one defined.
	 *
	 * @return string|null
	 */
	public function getWordmarkUrl(): ?string {
		if ( $this->wordmarkEnabled ) {
			$file = $this->getWordmarkFile();

			if ( $file ) {
				return $file->getUrl();
			}
		}

		return null;
	}

	/**
	 * Get the wordmark file to use.
	 *
	 * This ignores $wgMirageEnableImageWordmark.
	 *
	 * @return File|null
	 */
	public function getWordmarkFile(): ?File {
		foreach ( self::WORDMARK_FILE_NAMES as $name ) {
			$title = $this->titleFactory->makeTitle( NS_FILE, $name );

			$file = $this->repoGroup->findFile( $title );

			if ( $file && $file->exists() ) {
				return $file;
			}
		}

		return null;
	}
}
