<?php
/**
 * As Dynamic Sources can be packaged with multiple plugins, this number makes sure that the latest
 * version is loaded. Raise the number by 1 right before you merge it to develop.
 *
 * 100000 = Version 1.0.0
 * 101000 = Version 1.0.1
 * 101001 = The  1 merge to develop while working on 1.0.2.
 * ...
 * 101019 = The 19 merge to develop while working on 1.0.2.
 * 102000 = Version 1.0.2
 *
 * ...and so on...
 */
$toolset_dynamic_sources_version = 105000;

/**
 * Priority is: 100000 - $toolset_dynamic_sources_version <= 0
 * This makes sure that the highest version number is called first.
 */
add_action(
	'init',
	function() use ( $toolset_dynamic_sources_version ) {
		if ( defined( 'TOOLSET_DYNAMIC_SOURCES_LOADED' ) ) {
			// A more recent version of Toolset Dynamic Sources is already active.
			return;
		}

		// Define TOOLSET_COMMON_ES_LOADED so any older instance of Common ES is not loaded.
		define( 'TOOLSET_DYNAMIC_SOURCES_LOADED', $toolset_dynamic_sources_version );

		// Apply a new init callback to actually load Dynamic Sources on priority 1.
		add_action(
			'init',
			function() {
				// Register Autoloader
				require_once __DIR__ . '/../psr4-autoload.php';

				// Bootstrap DS
				new Toolset\DynamicSources\DynamicSources();
				do_action( 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize' );
			},
			1
		);
	},
	100000 - $toolset_dynamic_sources_version
);
