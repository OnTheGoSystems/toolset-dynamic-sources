<?php

namespace Toolset\DynamicSources\Scripts;

use Composer\Installer\PackageEvent;

class Composer {
	const PACKAGE_NAME = 'toolset/dynamic-sources';

	public static function dsNpmInstall( $event ) {
		static::runNpmCommand( 'install --only=prod', $event );
	}

	public static function dsNpmRunBuild( $event ) {
		static::runNpmCommand( 'run build', $event );
	}

	public static function dsPackageUpdated( PackageEvent $event ) {
		$installedPackage = $event->getOperation()->getInitialPackage()->getName();

		if ( self::PACKAGE_NAME === $installedPackage ) {
			static::dsNpmInstall( $event );
			static::dsNpmRunBuild( $event );
		}
	}

	protected static function runNpmCommand( $command, $event ) {
		$ds_path = 'vendor/' . self::PACKAGE_NAME;
		$event->getIO()->write(">> Running npm $command in $ds_path");
		exec( "cd $ds_path && npm $command" );
	}
}
