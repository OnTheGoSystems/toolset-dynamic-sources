<?php

namespace Toolset\DynamicSources\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class Common
 * @package ToolsetDynamicSources\Composer
 *
 * @since 1.0.0
 */
class Common {
	const PACKAGE_NAME = 'toolset/dynamic-sources';

	/**
	 * Run NPM command
	 *
	 * @param $command
	 */
	public static function runNpmCommand( $command ) {
		$ds_path = 'vendor/' . self::PACKAGE_NAME;
		exec( "cd $ds_path && npm $command" );
	}
}
