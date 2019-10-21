<?php

namespace Toolset\DynamicSources\Integrations;

class Views {
	/** @var string */
	private $content_template_post_type;

	/** @var string */
	private $wpa_helper_post_type;

	/** @var array */
	private $content_template_get_instance;

	/** @var array */
	private $view_get_instance;

	public function __construct(
		array $view_get_instance,
		array $content_template_get_instance,
		$content_template_post_type,
		$wpa_helper_post_type
	) {
		if ( ! is_string( $content_template_post_type ) ) {
			throw new \InvalidArgumentException( 'The Content Template post type argument ($content_template_post_type) has to be a string.' );
		}

		$this->content_template_post_type = $content_template_post_type;
		$this->wpa_helper_post_type = $wpa_helper_post_type;
		$this->content_template_get_instance = $content_template_get_instance;
		$this->view_get_instance = $view_get_instance;
	}

	/**
	 * Class initialization
	 */
	public function initialize() {
		add_filter( 'toolset/dynamic_sources/filters/get_dynamic_sources_data', array( $this, 'integrate_views_info_for_dynamic_sources' ) );

		add_action( 'rest_api_init', array( $this, 'register_content_template_preview_post' ) );

		add_action( 'toolset/dynamic_sources/filters/post_type_for_source_context', array( $this, 'adjust_post_types_for_source_context_in_cts' ), 10, 2 );

		add_action( 'toolset/dynamic_sources/filters/post_type_for_source_context', array( $this, 'adjust_post_types_for_source_context_in_view' ), 10, 2 );

		add_filter( 'toolset/dynamic_sources/filters/shortcode_post', array( $this, 'maybe_get_preview_post_id_for_ct_with_post_content_source' ), 10, 4 );

		add_filter( 'toolset/dynamic_sources/filters/shortcode_post', array( $this, 'maybe_get_preview_post_id_for_wpa_with_post_content_source' ), 10, 4 );

		add_filter( 'toolset/dynamic_sources/filters/post_sources', array( $this, 'maybe_exclude_post_content_source_from_post_sources' ) );
	}

	public function adjust_post_types_for_source_context_in_cts( $post_type, $post_id ) {
		if ( $this->content_template_post_type === $post_type ) {
			$post_type = $this->get_assigned_single_post_types( $post_id );
		}

		return $post_type;
	}

	public function adjust_post_types_for_source_context_in_view( $post_type, $post_id ) {
		if ( is_admin() || ! $post_id ) {
			return $post_type;
		}

		$view_block_name = 'toolset-views/view-editor';

		$post = get_post( $post_id );

		if (
			! has_blocks( $post->post_content ) ||
			strpos( $post->post_content, $view_block_name ) < 0
		) {
			return $post_type;
		}

		$view_post_types = array();

		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			if ( $view_block_name === $block['blockName'] ) {
				$view_post_types = array_merge( $view_post_types, $this->maybe_get_view_block_post_types( $block ) );
			}
		}

		if ( ! empty( $view_post_types ) ) {
			if ( ! is_array( $post_type ) ) {
				$post_type = [ $post_type ];
			}

			$post_type = array_merge( $post_type, $view_post_types );
		}

		return $post_type;
	}

	private function maybe_get_view_block_post_types( $block ) {
		$view_id = toolset_getnest(
			$block,
			array( 'attrs', 'viewId' ),
			toolset_getnest( $block, array( 'attrs', 'view', 'ID' ), false )
		);

		if ( ! $view_id ) {
			return [];
		}

		$view = call_user_func( $this->view_get_instance, $view_id );

		if ( 'posts' !== $view->query_type ) {
			return [];
		}

		return $view->view_settings['post_type'];
	}

	public function register_content_template_preview_post() {
		register_meta(
			'post',
			'tb_preview_post',
			array(
				'object_subtype' => $this->content_template_post_type,
				'show_in_rest' => true,
				'single' => true,
				'type' => 'number',
			)
		);
	}

	private function get_preview_posts( $post_types = null ) {
		if ( ! $post_types ) {
			$args = array(
				'public' => true,
			);
			$post_types = get_post_types( $args );
		}

		$posts_for_assigned_post_type = array();
		foreach ( $post_types as $post_type ) {
			if ( ! is_string( $post_type ) ) {
				if ( isset( $post_type['post_type_name'] ) ) {
					$post_type = $post_type['post_type_name'];
				} else {
					$post_type = '';
				}
			}

			if ( in_array( $post_type, array( 'attachment' ), true ) ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object || ! $post_type_object->public ) {
				continue;
			}

			$posts_for_assigned_post_type = array_merge(
				get_posts(
					array(
						'post_type' => $post_type,
						'posts_per_page' => 5,
					)
				),
				$posts_for_assigned_post_type
			);
		}

		return $posts_for_assigned_post_type;
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
	 * Gets the assigned post type for the Content Template either if it is a Content Template assigned to some post types
	 * or a Content Template that wraps a View's loop items. In the latter case, this method retuns the post type(s) the
	 * View is selected to list items from.
	 *
	 * @return array
	 */
	private function get_assigned_single_post_types( $post_id = null ) {
		$post_id = $post_id ?: get_the_ID();

		// When the editor autosaves, the post type is "revision", so we need to find the parent of the revision post
		// and get its post ID.
		if ( 'revision' === get_post_type( $post_id ) ) {
			$post_id = wp_get_post_parent_id( $post_id );
		}

		if ( ! $post_id ) {
			return array();
		}

		$ct = call_user_func( $this->content_template_get_instance, $post_id );

		$assigned_single_post_types = array_map( function( $item ) { return $item['post_type_name']; }, $ct->get_assigned_single_post_types() );

		if ( empty( $assigned_single_post_types ) ) {
			$view_loop_id = (int) $ct->get_postmeta( '_view_loop_id' );
			if ( $view_loop_id > 0 ) {
				$assigned_single_post_types = $this->get_parent_loop_post_types( (int) $view_loop_id );
			}
		}

		if ( empty( $assigned_single_post_types ) ) {
			// Turn the output of $ct->get_assigned_loops into proper format for Types
			$assigned_single_post_types = array_filter(
				array_map(
					function ( $loop_definition ) {
						return (
						array_key_exists( 'post_type_name', $loop_definition )
							? $loop_definition['post_type_name']
							: null
						);
					}, $ct->get_assigned_loops( 'post_type' )
				),
				function ( $post_type_slug ) {
					return is_string( $post_type_slug ) && ! empty( $post_type_slug );
				}
			);
		}

		// todo: Remove the next line when the time comes.
		// Limit the preview posts offered by the post selector in CTs only to posts from a single (the first one) post type.
		// More info here https://onthegosystems.myjetbrains.com/youtrack/issue/toolsetblocks-160#focus=streamItem-102-336782.0-0
		$assigned_single_post_types = isset( $assigned_single_post_types[0] ) ? array( $assigned_single_post_types[0] ) : array();
		// todo: Stop removing!

		return $assigned_single_post_types;
	}

	public function integrate_views_info_for_dynamic_sources( $localization_array ) {
		if ( $this->content_template_post_type !== get_post_type() ) {
			return $localization_array;
		}

		$assigned_single_post_types = $this->get_assigned_single_post_types();

		$preview_posts = $this->get_preview_posts( $assigned_single_post_types );

		if ( empty( $preview_posts ) ) {
			return $localization_array;
		}

		$preview_posts = array_map(
			function( $post ) {
				return array(
					'label' => $post->post_title,
					'value' => $post->ID,
					'guid' => $post->guid,
				);
			},
			$preview_posts
		);

		$post_preview = absint( get_post_meta( get_the_ID(), 'tb_preview_post', true ) );

		if ( $post_preview <= 0 ) {
			$post_preview = $preview_posts[0]['value'];
		} else {
			// Make sure we do include the selected pos to preview
			$preview_posts[] = array(
				'label' => get_the_title( $post_preview ),
				'value' => $post_preview,
				'guid' => get_the_guid( $post_preview ),
			);

			// Avoid duplicates
			$serialized = array_map( 'serialize', $preview_posts );
			$unique = array_unique( $serialized );
			$preview_posts = array_intersect_key( $preview_posts, $unique );
		}

		$localization_array['previewPosts'] = $preview_posts;

		$localization_array['postPreview'] = $post_preview;

		$localization_array['cache'] = apply_filters( 'toolset/dynamic_sources/filters/cache', array(), $post_preview );

		return $localization_array;
	}

	/**
	 * Returns the preview post ID if the "post" is the ID of a Content Template and the selected "source" is "post-content".
	 * If for some reason there is no preview post ID meta for the Content Template, it returns null.
	 *
	 * @param $post
	 * @param $post_provider
	 * @param $source
	 * @param $field
	 *
	 * @return int|null
	 */
	public function maybe_get_preview_post_id_for_ct_with_post_content_source( $post, $post_provider, $source, $field ) {
		if (
			'post-content' !== $source ||
			get_post_type( $post ) !== $this->content_template_post_type
		) {
			return $post;
		}

		$preview_post_id = absint( get_post_meta( $post, 'tb_preview_post', true ) );

		if ( $preview_post_id <= 0 ) {
			$post = null;
		} else {
			$post = $preview_post_id;
		}

		return $post;
	}

	/**
	 * Filters the Post Sources by excluding the PostContent sources when not needed.
	 *
	 * @param array $post_sources The Post Sources.
	 *
	 * @return array The filtered Post Sources.
	 */
	public function maybe_exclude_post_content_source_from_post_sources( $post_sources ) {
		// Do not offer the PostContent source outside of Content Templates or in new post pages.
		global $pagenow;
		$post = (int) toolset_getget( 'post', 0 );
		$should_exclude_post_content_source = false;

		switch ( $pagenow ) {
			case 'post.php':
				if (
					! in_array(
						get_post_type( $post ),
						array(
							$this->content_template_post_type,
							$this->wpa_helper_post_type
						)
					)
				) {
					$should_exclude_post_content_source = true;
				}
				break;
			case 'post-new.php':
				$should_exclude_post_content_source = true;
				break;
		}

		if ( $should_exclude_post_content_source ) {
			$post_sources = array_filter(
				$post_sources,
				function( $source ) {
					return ( 'PostContent' !== $source );
				}
			);
		}

		return $post_sources;
	}

	/**
	 * Returns the preview post ID if the "post" is the ID of a WordPress Archive and the selected "source" is "post-content".
	 * If for some reason there is no preview post ID meta for the Content Template, it returns null.
	 *
	 * @param $post
	 * @param $post_provider
	 * @param $source
	 * @param $field
	 *
	 * @return int|null
	 */
	public function maybe_get_preview_post_id_for_wpa_with_post_content_source( $post, $post_provider, $source, $field ) {
		if (
			'post-content' === $source &&
			get_post_type( $post ) === $this->wpa_helper_post_type
		) {
			return null;
		}

		return $post;
	}

}
