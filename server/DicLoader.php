<?php

namespace Toolset\DynamicSources;


/**
 * Dependency Injection Wrapper
 * The one and only Singelton
 */
class DicLoader {

	/** @var DicLoader */
	private static $instance;

	/** @var \Auryn\Injector */
	private $dic;


	/**
	 * @returns DicLoader
	 */
	public static function get_instance() {
		return self::$instance = self::$instance ?: new self();
	}

	/**
	 * DicLoader constructor.
	 */
	private function __construct() {
		$this->dic = apply_filters( 'toolset_dic', false );

		if( $this->dic === false && class_exists( '\Auryn\Injector' ) ) {
			// DS used outside of Toolset.
			$this->dic = new \Auryn\Injector();
		}

		if( $this->dic === false ) {
			// No Toolset and Auryn could not be found. (Composer autoload not used.)
			throw new \Exception( 'Dynamic Sources: Auryn could not be loaded.' );
		}
	}

	/**
	 * @return \Auryn\Injector
	 */
	public function get_dic() {
		return $this->dic;
	}
}
