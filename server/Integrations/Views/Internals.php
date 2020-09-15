<?php

namespace Toolset\DynamicSources\Integrations\Views;

class Internals {
	/** @var array */
	private $view_get_instance;

	/** @var array */
	private $content_template_get_instance;

	public function __construct(
		array $view_get_instance,
		array $content_template_get_instance
	) {
		$this->view_get_instance = $view_get_instance;
		$this->content_template_get_instance = $content_template_get_instance;
	}

	/**
	 * Gets the assigned post type for the Content Template either if it is a Content Template assigned to some post types
	 * or a Content Template that wraps a View's loop items. In the latter case, this method retuns the post type(s) the
	 * View is selected to list items from.
	 *
	 * @param int|null $post_id The Content Template post ID.
	 *
	 * @return array
	 */
	public function get_assigned_post_types( $post_id = null ) {
		$post_id = $post_id ? $post_id : get_the_ID();

		// When the editor autosaves, the post type is "revision", so we need to find the parent of the revision post
		// and get its post ID.
		if ( 'revision' === get_post_type( $post_id ) ) {
			$post_id = wp_get_post_parent_id( $post_id );
		}

		if ( ! $post_id ) {
			return array();
		}

		$post_id = apply_filters( 'wpv_filter_get_original_language_ct_post_id_from_translation', $post_id );

		$ct = call_user_func( $this->content_template_get_instance, $post_id );

		// Get the single post types the CT is assigned to.
		$assigned_single_post_types = array_map(
			function( $item ) {
				return $item['post_type_name'];
			},
			$ct->get_assigned_single_post_types()
		);

		// If no single post types available, maybe it's a CT assigned to a View loop.
		$assigned_single_post_types_from_view_loop = array();
		if ( empty( $assigned_single_post_types ) ) {
			$view_loop_id = (int) $ct->get_postmeta( '_view_loop_id' );
			if ( $view_loop_id > 0 ) {
				$assigned_single_post_types_from_view_loop = $this->get_parent_loop_post_types( (int) $view_loop_id );
			}
		}

		// Get the loops a Content Template is assigned to.
		$assigned_post_types_from_loops = array_filter(
			array_map(
				function ( $loop_definition ) {
					return (
						array_key_exists( 'post_type_name', $loop_definition )
							? $loop_definition['post_type_name']
							: null
					);
				},
				$ct->get_assigned_loops( 'post_type' )
			),
			function ( $post_type_slug ) {
				return is_string( $post_type_slug ) && ! empty( $post_type_slug );
			}
		);

		return array_merge(
			$assigned_single_post_types,
			$assigned_single_post_types_from_view_loop,
			$assigned_post_types_from_loops
		);
	}

	/**
	 * Retrieves the post types a View or WPA is set to list posts from.
	 * If a View queries something else other than posts, it returns an empty array.
	 *
	 * @param int $view_id The ID of the View/WPA.
	 *
	 * @return array|mixed
	 */
	private function get_parent_loop_post_types( $view_id ) {
		$view = call_user_func( $this->view_get_instance, $view_id );

		if ( $view->is_a_view() ) {
			return $this->get_view_loop_post_types( $view );
		}

		if ( $view->is_a_wordpress_archive() ) {
			return $this->get_wpa_loop_post_types( $view );
		}

		return array();
	}

	/**
	 * Retrieves the posts that a View is set to list posts from.
	 * If a View queries something else other than posts, it returns an empty array.
	 *
	 * @param WPV_View_Base $view
	 * @return array
	 */
	private function get_view_loop_post_types( $view ) {
		if ( 'posts' !== $view->query_type ) {
			return array();
		}

		if ( ! isset( $view->view_settings['post_type'] ) ) {
			return array();
		}

		return $view->view_settings['post_type'];
	}

	/**
	 * Retrieves the posts that a WordPress Archive is set to list.
	 *
	 * @param WPV_View_Base $wpa
	 * @return array
	 */
	private function get_wpa_loop_post_types( $wpa ) {
		return apply_filters( 'wpv_get_post_types_for_wordpress_archive', array(), $wpa->id );
	}

	/**
	 * Populates the array of post(s) using the current Content Template (if any).
	 *
	 * @param null|int $limit The number of posts to get.
	 *
	 * @return array|WP_Post[]
	 */
	public function maybe_get_single_assigned_posts_for_ct( $limit = 5 ) {
		$post_id = get_the_ID();

		$ct = call_user_func( $this->content_template_get_instance, $post_id );

		return $ct->get_posts_using_this( '*', 'flat_array', $limit );
	}

	/**
	 * Gets a set of the first 5 (at most) preview posts for a Content Template.
	 *
	 * The posts are selected using the following scenario:
	 * - If the Content Template is not assigned to any single pages/post archives/taxonomy archives the method tries to
	 *   populate the single posts it is assigned to.
	 * - If no single posts come up from the previous step, a list of random posts is been populated.
	 * - In any case the Content Template is not assigned to any single pages/post archives/taxonomy archives the method
	 *   tries to populate the first five posts that can be relevant to the Content Template assignment.
	 *
	 * @param null $post_types
	 *
	 * @return array|int[]|\WP_Post[]
	 */
	public function get_preview_posts( $post_types = null ) {
		// Total number of preview posts.
		$limit = 5;

		$include = '';

		if ( ! $post_types ) {
			$args = array(
				'public' => true,
			);

			$post_types = get_post_types( $args );

			$single_assigned_posts_for_ct = $this->maybe_get_single_assigned_posts_for_ct( $limit );

			if ( ! empty( $single_assigned_posts_for_ct ) ) {
				$include = implode( ',', $single_assigned_posts_for_ct );
			}
		}

		$post_types = array_diff( $post_types, array( 'attachment' ) );

		$post_types = array_filter(
			$post_types,
			function( $post_type ) {
				$post_type_object = get_post_type_object( $post_type );
				return $post_type_object && $post_type_object->public;
			}
		);

		// Post status is set to "any" for the following reasons:
		// - 'Publish' is self-explanatory.
		// - 'Draft' is for the case a CT is assigned to a single post which is not published yet but the user still needs
		// to be able to preview the CT.
		// - 'Trash' is for the case CT is assigned to a single trashed post, so when the CT edit page loads, it might
		// display completely unrelated stuff if a random preview post is used for it instead.
		return get_posts(
			array(
				'post_type' => $post_types,
				'posts_per_page' => $limit,
				'suppress_filters' => false,
				'post_status' => 'any',
				'include' => $include,
			)
		);
	}

	public function maybe_get_view_block_post_types( $block ) {
		$view_id = toolset_getnest(
			$block,
			array( 'attrs', 'viewId' ),
			toolset_getnest( $block, array( 'attrs', 'view', 'ID' ), false )
		);

		if ( ! $view_id ) {
			return array();
		}

		$view = call_user_func( $this->view_get_instance, $view_id );

		if ( 'posts' !== $view->query_type ) {
			return array();
		}

		return $view->view_settings['post_type'];
	}
}
