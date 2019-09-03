<?php
// Register PSR4 autloader.
spl_autoload_register( function( $class ) {
	$prefix = 'Toolset\\DynamicSources\\';
	$root_dir  = __DIR__ . '/server/';
	
	// Check if $class having $root name.
	$len = strlen( $prefix );
	if( strncmp( $prefix, $class, $len ) !== 0 ) {
		// Foreign class.
		return;
	}

	// Get class without prefix.
	$class_without_prefix = substr( $class, $len );

	// Build class path.
	$class_path = $root_dir . str_replace('\\', '/', $class_without_prefix) . '.php';

	// Load file if exists.
	if ( file_exists( $class_path ) ) {
		require $class_path;
	}
});
