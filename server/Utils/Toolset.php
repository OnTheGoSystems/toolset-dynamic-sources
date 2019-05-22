<?php
/**
 * Toolset utils
 *
 * @since 1.0.0
 */

namespace Toolset\DynamicSources\Utils;

class Toolset {

	/**
	 * Returns if Views is enabled
	 *
	 * @return boolean
	 */
	public function is_views_enabled() {
		$toolset_views_is_active_class_name = '\Toolset_Condition_Plugin_Views_Active';
		if ( class_exists( $toolset_views_is_active_class_name ) ) {
			$toolset_views_is_active = new $toolset_views_is_active_class_name();
			return $toolset_views_is_active->is_met();
		}
		return false;
	}

	/**
	 * Returns if Types is enabled
	 *
	 * @return boolean
	 */
	public function is_types_enabled() {
		return apply_filters( 'types_is_active', false );
	}
}
