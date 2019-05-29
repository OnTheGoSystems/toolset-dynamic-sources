<?php
$DS_VERSION = 100000;

if ( ! function_exists( 'ts_dynamic_sources_adjust_ds_instance' ) ) {
	/**
	 * Adjusts the Composer autoloader to load the most recent version of the Dynamic Sources API.
	 *
	 * It registers a filter that collects all the availabe Dynamic Sources API versions. When the time comes,
	 * it feeds the Composer autoloader with the path to the most recent version of the Dynamic Sources API available.
	 *
	 * @param string $plugin_path     The root path of the plugin.
	 * @param int    $ds_version      The version of the Dynamic Sources API.
	 * @param string $ds_path         The version of the Dynamic Sources API.
	 */
	function ts_dynamic_sources_adjust_ds_instance( $plugin_path, $ds_version, $ds_path ) {
		add_filter(
			'toolset/dynamic_sources/filters/get_ds_instances',
			function( $instances ) use ( $ds_version, $ds_path ) {
				$instances[ $ds_version ] = $ds_path;
				return $instances;
			}
		);

		$ds_instances = apply_filters( 'toolset/dynamic_sources/filters/get_ds_instances', array() );
		ksort( $ds_instances );

		$loader = require $plugin_path . '/vendor/autoload.php';
		$loader->setPsr4( 'Toolset\\DynamicSources\\', end( $ds_instances ) );

		if ( ! has_action( 'init', 'initialize_ds' ) ) {
			add_action( 'init', 'initialize_ds', 1 );
		}
	}
}

if ( ! function_exists( 'initialize_ds' ) ) {
	function initialize_ds() {
		new Toolset\DynamicSources\DynamicSources();
		do_action( 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize' );
	}
}

return array(
	'version' => $DS_VERSION,
	'path' => __DIR__,
);
