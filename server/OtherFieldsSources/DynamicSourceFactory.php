<?php

namespace Toolset\DynamicSources\OtherFieldsSources;


use Toolset\DynamicSources\DicLoader;
use Toolset\DynamicSources\PostProvider;
use Toolset\DynamicSources\Sources\Source;

/**
 * Dynamically generate dynamic sources for non Toolset Custom Field Groups.
 */
class DynamicSourceFactory {

	/**
	 * Get the list of sources from the post_meta table
	 *
	 * @param PostProvider[] $post_providers
	 * @return Source[]
	 */
	public function get_sources( $post_providers ) {
		$dic = DicLoader::get_instance()->get_dic();
		$toolset_utils = $dic->make( \Toolset\DynamicSources\Utils\Toolset::class );

		if ( ! $toolset_utils->is_views_enabled() ) {
			if ( ! $post_providers ) {
				return array();
			}
			global $wpdb;
			$post_types = [];
			foreach( $post_providers as $post_provider ) {
				$post_types = array_merge( $post_types, $post_provider->get_post_types() );
			}
			if ( empty( $post_types ) ) {
				return [];
			}
			$format = implode( ', ', array_fill( 0, count( $post_types ), '%d') );
			// Performance tip: having 3980 posts and 12038 postmeta, this query returns 7961 records in 0s
			$query = "SELECT meta_key
			FROM {$wpdb->postmeta} as meta
			LEFT JOIN {$wpdb->posts} as posts ON meta.post_id = posts.ID and post_type in ({$format})
			WHERE left(meta_key, 1) <> '_'
			GROUP BY 1";
			$results = array_map(
				function ( $meta ) {
					return $meta->meta_key;
				},
				$wpdb->get_results( $wpdb->prepare( $query, $post_types ) )
			);
		} else {
			// Here it was preferred to use the Views cache for postmeta instead of making a DB query because in large sites
			// like wpml.org, with tons of postmeta, this query was really heavy, creating all sorts of problems.
			// Using the filter the DB is accessed only once for this reason.
			$results = apply_filters( 'wpv_get_visible_postmeta_cache', array() );
		}

		$sources = [];
		foreach ( $results as $meta ) {
			if ( ! $this->is_valid_field( $meta ) ) {
				continue;
			}
			$sources[] = new PostField( $meta );
		}

		return $sources;
	}

	/**
	 * Filter meta items
	 *
	 * @param object $meta Meta element
	 * @return boolean
	 */
	private function is_valid_field( $meta ) {
		if ( preg_match( '/^wpcf-/', $meta ) ) {
			return false;
		}
		if ( preg_match( '/^wpml-/', $meta ) ) {
			return false;
		}
		return true;
	}

}
