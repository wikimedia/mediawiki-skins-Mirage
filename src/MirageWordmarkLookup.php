<?php

namespace MediaWiki\Skins\Mirage;

use File;
use RepoGroup;
use TitleFactory;
use const NS_FILE;

class MirageWordmarkLookup {
	/** @var TitleFactory */
	private $titleFactory;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var bool */
	private $wordmarkEnabled;

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

			if ( $file && $file->exists() ) {
				return $file->getUrl();
			}
		}

		return null;
	}

	/**
	 * @return File|null
	 */
	public function getWordmarkFile(): ?File {
		$title = $this->titleFactory->makeTitle( NS_FILE, 'Mirage-wordmark.png' );

		return $this->repoGroup->findFile( $title ) ?: null;
	}
}
