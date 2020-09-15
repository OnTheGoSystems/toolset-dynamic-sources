<?php

namespace Toolset\DynamicSources\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class BundleBuild
 * @package Toolset\DynamicSources\Composer
 *
 * @since 1.0.0
 */
class BundleBuild {

	/**
	 * On BundleBuild command re-create the build files.
	 *
	 * @param $event
	 */
	public static function run( $event ) {
		$event->getIO()->write( '-------------------------------------------------------' );
		$event->getIO()->write( '-- TOOLSET DYNAMIC SOURCES: Generating build files.  --' );
		$event->getIO()->write( '-------------------------------------------------------' );
		Common::runNpmCommand( 'run build' );

		$event->getIO()->write( 'Completed.' );
	}
}
