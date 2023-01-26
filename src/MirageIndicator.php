<?php

namespace MediaWiki\Skins\Mirage;

class MirageIndicator extends MirageIcon {
	/**
	 * @param string $indicator
	 */
	public function __construct( string $indicator ) {
		parent::__construct( "indicator-$indicator", self::ICON_SMALL );
		$this->hideLabel();
	}

	/** @inheritDoc */
	public function toClasses(): string {
		return 'skin-mirage-ooui-indicator ' . parent::toClasses();
	}
}
