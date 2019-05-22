<?php

namespace Toolset\DynamicSources;

use Auryn\Injector;

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
		$this->dic = new Injector();
	}

	/**
	 * @return \Auryn\Injector
	 */
	public function get_dic() {
		return $this->dic;
	}
}
