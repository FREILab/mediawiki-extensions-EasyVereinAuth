<?php
namespace MediaWiki\Extension\EasyVereinAuth;

use GlobalVarConfig;

class Config extends GlobalVarConfig {
	public function __construct() {
		parent::__construct( 'wgEasyVereinAuth_' );
	}

	/**
	 * Factory method for MediaWikiServices
	 * @return Config
	 */
	public static function newInstance() {
		return new self();
	}
}
