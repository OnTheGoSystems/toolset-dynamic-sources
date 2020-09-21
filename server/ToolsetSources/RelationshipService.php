<?php

namespace Toolset\DynamicSources\ToolsetSources;

use Toolset\DynamicSources\SourceContext\SourceContext;
use Toolset\DynamicSources\SourceContext\ViewSourceContext;

/**
 * Layer for communicating with Toolset Common regarding relationships.
 */
class RelationshipService {
	/**
	 * For a given post type, return relationships that can be used for dynamic content sources.
	 * That means relationships, where we can have only a single post on the other side.
	 *
	 * @param string $post_type_slug
	 * @param SourceContext $source_context
	 *
	 * @return PostRelationshipModel[]
	 */
	public function get_relationships_acceptable_for_sources( $post_type_slug, SourceContext $source_context ) {

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return [];
		}

		$base_args = [
			'type_constraints' => [
				'parent' => [
					'domain' => 'posts',
				],
				'child' => [
					'domain' => 'posts',
				],
			],
			'origin' => 'any',
		];

		$one_to_many_args = $base_args;
		$one_to_many_args['cardinality'] = 'one-to-many';
		$one_to_many_args['type_constraints']['child']['type'] = $post_type_slug;

		$one_to_many_relationships = toolset_get_relationships( $one_to_many_args );

		$one_to_one_args = $base_args;
		$one_to_one_args['cardinality'] = 'one-to-one';
		$one_to_one_args['type_constraints']['any']['type'] = $post_type_slug;

		$one_to_one_relationships = toolset_get_relationships( $one_to_one_args );

		// Check if there is a M2M relationship that effectively becomes O2M due to a View filter.
		if ( $source_context instanceof ViewSourceContext ) {
			$view_settings = apply_filters( 'wpv_view_settings', [], $source_context->get_view_id() );
			// Special case when in editor, View has been created, but not saved for the first time yet. At that
			// specific moment, $view_settings still don't contain post_relationship_* data for the View proper, but we
			// can get it from preview View. Since it would be a little hell to pass also preview View Id and View saved
			// status through all the layers, here we use a little hacky, but in practice correct assumption that
			// preview View Id equals View Id + 1.
			if ( ! array_key_exists( 'post_relationship_mode', $view_settings ) ) {
				$view_settings = apply_filters( 'wpv_view_settings', [], $source_context->get_view_id() + 1 );
			}
		} else {
			$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		}
		if (
			array_key_exists( 'post_relationship_slug', $view_settings ) &&
			array_key_exists( 'post_relationship_mode', $view_settings ) &&
			in_array( 'top_current_post', $view_settings['post_relationship_mode'], true )
		) {
			$filtered_m_2_m_relationship = toolset_get_relationship( $view_settings['post_relationship_slug'] );
			if ( ! empty( $filtered_m_2_m_relationship ) ) {
				$filtered_m_2_m_relationship['post_type'] = $post_type_slug;
				$filtered_m_2_m_relationship['post_relationship_mode'] = $view_settings['post_relationship_mode'];
				$filtered_m_2_m_relationships = [ $filtered_m_2_m_relationship ];
			} else {
				$filtered_m_2_m_relationships = [];
			}
		} else {
			$filtered_m_2_m_relationships = [];
		}

		// If this post is intermediary, we can offer its related posts as sources.
		$query = new \Toolset_Relationship_Query_V2();
		$results = $query
			->add( $query->has_domain( 'posts' ) )
			->add( $query->intermediary_type( $post_type_slug ) )
			->get_results();
		$service = new \OTGS\Toolset\Common\M2M\PublicApiService();
		$many_to_many_relationships = array_map( function( $relationship ) use ( $service ) {
			return $service->format_relationship_definition( $relationship );
		}, $results );

		return array_map(
			function( $definition_array ) {
				return new PostRelationshipModel( $definition_array );
			},
			array_merge(
				$one_to_one_relationships,
				$one_to_many_relationships,
				$filtered_m_2_m_relationships,
				$many_to_many_relationships
			)
		);
	}

	/**
	 * Obtain a related post.
	 *
	 * @param int|\WP_Post $related_to_post
	 * @param PostRelationshipModel $relationship
	 * @param string $target_role
	 *
	 * @return int|null
	 */
	public function get_related_post( $related_to_post, PostRelationshipModel $relationship, $target_role ) {
		if ( $relationship->is_views_filtered_o_2_m() ) {
			return $this->get_intermediary( $related_to_post, $relationship );
		} elseif ( $relationship->is_intermediary() ) {
			return $this->get_single_intermediary_related_post(
				$related_to_post,
				$relationship->get_slug(),
				$target_role
			);
		}
		return toolset_get_related_post( $related_to_post, $relationship->get_slug(), $target_role );
	}

	/**
	 * @param int|\WP_Post $related_to_post
	 * @param PostRelationshipModel $relationship
	 *
	 * @return int
	 */
	private function get_intermediary( $related_to_post, PostRelationshipModel $relationship ) {
		$top_current_post = $this->get_top_current_post();
		$role = $relationship->get_views_filtered_o_2_m_side() ?: 'child';

		$sides = [ $role => $related_to_post ];
		// Check if we were able to get the top_current_post. If not, we'll just send a less precise query, which is
		// the best we can do until needed data is available (probably after saving the post, CT, View or whatever is
		// missing and can't be had).
		if ( $top_current_post ) {
			$top_current_post_role = $this->get_other_side_role( $role );
			$sides[ $top_current_post_role ] = $top_current_post;
		}

		$post_ids = toolset_get_related_posts(
			$sides,
			$relationship->get_slug(),
			[
				'role_to_return' => 'intermediary',
				'limit' => 1,
			]
		);

		return array_shift( $post_ids );
	}

	/**
	 * Getting the top_current_post is a dark art when inside a REST request...
	 *
	 * @return int|\WP_Post|null
	 */
	private function get_top_current_post() {
		// Not inside a REST request, life is easy.
		$top_current_post = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );

		// If we have the View id, and we are on a regular post, View parent post is the top_current_post.
		if ( ! $top_current_post ) {
			$view_id = toolset_getget( 'view-id', null );
			$view_parent_post_id = apply_filters( 'wpv_filter_get_view_parent_post_id', null, $view_id );
			$top_current_post = get_post( $view_parent_post_id );
		}

		// We can also try to get the top current post from HTTP_REFERER, as a fallback.
		if ( ! $top_current_post ) {
			if ( array_key_exists( 'HTTP_REFERER', $_SERVER ) ) {
				$query = parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_QUERY );
				$query_data = explode( '&', $query );
				$keyed_query_data = [];
				foreach ( $query_data as $item ) {
					$exploded_item = explode( '=', $item );
					$keyed_query_data[ $exploded_item[0] ] = $exploded_item[1];
				}
				if ( array_key_exists( 'post', $keyed_query_data ) ) {
					$top_current_post = get_post( $keyed_query_data['post'] );
				}
			}
		}

		// If we are inside a CT, $top_current_post is actually the preview post.
		if (
			$top_current_post &&
			'view-template' === $top_current_post->post_type
		) {
			$top_current_post = get_post_meta( $top_current_post->ID, 'tb_preview_post', true );
		}

		return $top_current_post;
	}

	/**
	 * @param string $role
	 *
	 * @return string
	 */
	private function get_other_side_role( $role ) {
		return 'parent' === $role ? 'child' : 'parent';
	}

	/**
	 * @param int|\WP_Post $related_to_post
	 * @param string $relationship_slug
	 * @param string $target_role
	 *
	 * @return int
	 */
	private function get_single_intermediary_related_post( $related_to_post, $relationship_slug, $target_role ) {
		$post_ids = toolset_get_related_posts( $related_to_post, $relationship_slug, [
			'query_by_role' => 'intermediary',
			'role_to_return' => $target_role,
		] );
		return array_shift( $post_ids );
	}

	/**
	 * @param string $role_name 'parent'|'child'|'intermediary'.
	 * @param string $relationship_slug
	 * @param null $definition_repository Just a testing dummy for a class from Toolset Common.
	 *
	 * @return RelationshipRole
	 */
	public function get_role_from_name( $role_name, $relationship_slug = '', $definition_repository = null ) {
		$role_label = '';
		$definition = null;

		if ( $relationship_slug ) {
			$definition_repository = $definition_repository ?:
				\Toolset_Relationship_Definition_Repository::get_instance();
			$definition = $definition_repository->get_definition( $relationship_slug );
		}

		// Possibly custom named and translated role...
		if ( $definition ) {
			$role_label = ucfirst( $definition->get_role_label_singular( $role_name ) );
		}
		// ..or fallback
		if ( ! $role_label ) {
			switch ( $role_name ) {
				case 'intermediary':
					$role_label = __( 'Intermediary', 'wpv-views' );
					break;
				case 'parent':
					$role_label = __( 'Parent', 'wpv-views' );
					break;
				case 'child':
				default:
					$role_label = __( 'Child', 'wpv-views' );
					break;
			}
		}

		return new RelationshipRole( $role_name, $role_label );
	}
}
