<?php

namespace Toolset\DynamicSources\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class PostPackageUpdate
 * @package Toolset\DynamicSources\Composer
 *
 * @since 1.0.0
 */
class PostPackageUpdate {
	/**
	 * On Post Package Update check if this package was updated and if so run the same
	 * command as on Post Install Cmd to update this package dependencies and build.
	 *
	 * @param $event
	 */
	public static function run( $event ) {
		$installedPackage = $event->getOperation()->getInitialPackage()->getName();

		if ( Common::PACKAGE_NAME === $installedPackage ) {
			PostInstallCmd::run( $event );
		}
	}
}
