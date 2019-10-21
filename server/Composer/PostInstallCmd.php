<?php

namespace Toolset\DynamicSources\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class PostInstallCmd
 * @package Toolset\DynamicSources\Composer
 *
 * @since 1.0.0
 */
class PostInstallCmd {

	/**
	 * On Post Install Cmd install this package's dependencies and create the build files.
	 *
	 * @param $event
	 */
	public static function run( $event ) {
		$event->getIO()->write( "-------------------------------------------------------" );
		$event->getIO()->write( "-- TOOLSET DYNAMIC SOURCES: Installing NPM Packages. --" );
		$event->getIO()->write( "-------------------------------------------------------" );
		Common::runNpmCommand( 'install --only=prod' );

		$event->getIO()->write( "-------------------------------------------------------" );
		$event->getIO()->write( "-- TOOLSET DYNAMIC SOURCES: Generating build files.  --" );
		$event->getIO()->write( "-------------------------------------------------------" );
		Common::runNpmCommand( 'run build' );

		$event->getIO()->write( "Completed." );
	}
}
