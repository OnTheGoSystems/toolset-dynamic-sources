<?php

namespace Toolset\DynamicSources;

use Toolset\DynamicSources\PostProviders\IdentityPost;
use Toolset\DynamicSources\PostProviders\CustomPost;
use Toolset\DynamicSources\PostProviders\PostProviders;
use Toolset\DynamicSources\Sources\Source;

class DynamicSources {
	const TOOLSET_DYNAMIC_SOURCES_SCRIPT_HANDLE = 'toolset_dynamic_sources_script';

	const TOOLSET_DYNAMIC_SOURCES_STYLE_HANDLE = 'toolset_dynamic_sources_style';

	const TOOLSET_DYNAMIC_SOURCES_JS_OBJECT_NAME = 'toolsetDynamicSourcesScriptData';

	const TOOLSET_BLOCKS_DYNAMIC_SOURCES_STORE = 'toolset/dynamic-sources';

	const POST_GROUP = 'post';

	const AUTHOR_GROUP = 'author';

	const SITE_GROUP = 'site';

	const COMMENTS_GROUP = 'comments';

	const MEDIA_GROUP = 'media';

	const OTHER_GROUP = 'other';

	const TEXT_CATEGORY = 'text';

	const NUMBER_CATEGORY = 'number';

	const IMAGE_CATEGORY = 'image';

	const DATE_CATEGORY = 'date';

	const AUDIO_CATEGORY = 'audio';

	const VIDEO_CATEGORY = 'video';

	const URL_CATEGORY = 'url';

	const HTML_CATEGORY = 'html';

	const EMBED_CATEGORY = 'embed';

	const VALUE_SEPARATOR = '#+*#';

	const CUSTOM_POST_TYPE_REGEXP = '/"(custom_post_type\|[^\|]+\|\d+)"/';

	const SHORTCODE = 'tb-dynamic';

	private $switched_post_data;

	/** @var Source[] */
	private $sources_for_registration = array();


	/** @var PostProvider[] */
	private $post_providers = array();


	/** @var SourceContext */
	private $source_context;

	/** @var SourceStorage */
	private $source_storage;

	/** @var Registration */
	private $sources_registration_factory;

	/** @var DicLoader */
	private $dic;

	private $registered_dynamic_sources_repo = array();

	public function __construct() {
		$this->dic = DicLoader::get_instance()->get_dic();

		add_action( 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize', array( $this, 'initialize' ) );
	}

	/**
	 * Initializes the Dynamic Sources API
	 */
	public function initialize() {
		if ( did_action( 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize' ) > 1 ) {
			return;
		}

		add_shortcode( 'tb-dynamic-container', array( $this, 'dynamic_container_shortcode_render' ) );

		add_shortcode( self::SHORTCODE, array( $this, 'dynamic_shortcode_render' ) );

		add_action( 'init', array( $this, 'initialize_toolset_fields_sources' ) );

		// The Views integration needs to be initialized before (priority < 10) the initialization of the sources, because "initialize_sources"
		// includes a filter added in the Views integration code.
		add_action( 'init', array( $this, 'initialize_views_integration' ), 9 );

		add_action( 'init', array( $this, 'initialize_sources' ) );

		add_action( 'init', array( $this, 'initialize_other_fields_sources' ) );

		add_action( 'rest_api_init', array( $this, 'initialize_rest' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_sources' ), 1 );

		add_action( 'toolset/dynamic_sources/actions/register_sources', array( $this, 'register_sources' ) );

		add_filter( 'toolset/dynamic_sources/filters/get_post_providers', array( $this, 'get_post_providers' ) );

		add_filter( 'toolset/dynamic_sources/filters/get_post_providers_for_select', array( $this, 'get_post_providers_for_select' ) );

		add_filter( 'toolset/dynamic_sources/filters/get_grouped_sources', array( $this, 'get_grouped_sources' ) );

		add_filter( 'toolset/dynamic_sources/filters/get_source_fields', array( $this, 'get_source_fields' ), 10, 4 );

		add_filter( 'toolset/dynamic_sources/filters/get_source_content', array( $this, 'get_source_content' ), 10, 5 );

		add_filter( 'toolset/dynamic_sources/filters/get_dynamic_sources_data', array( $this, 'get_dynamic_sources_data' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_localization_data' ) );

		// Replaces the shortcodes inside HTML attributes for normal posts.
		add_filter( 'the_content', array( $this, 'shortcode_render' ), -1 );

		// Replaces the shortcodes inside HTML attributes for Views.
		add_filter( 'wpv-pre-do-shortcode', array( $this, 'shortcode_render' ), -1 );

		// Replaces the shortcodes inside HTML attributes for Content Templates.
		add_filter( 'wpv_filter_content_template_output', array( $this, 'shortcode_render' ), -1 );

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->dic->make( \Toolset\DynamicSources\Integrations\WPML::class )->initialize();
		}

		$this->sources_registration_factory = $this->dic->make( Registration::class );

		$this->dic->make( \Toolset\DynamicSources\PostProviders\PostProviders::class )->initialize();
	}

	public function initialize_toolset_fields_sources() {
		// Toolset related sources.
		$toolset_sources_controller = $this->dic->make( ToolsetSources\Main::class );
		$toolset_sources_controller->initialize();
	}

	/**
	 * Post meta now goes in a different group
	 */
	public function initialize_other_fields_sources() {
		$sources_controller = $this->dic->make( OtherFieldsSources\Main::class );
		$sources_controller->initialize();
	}

	public function initialize_views_integration() {
		$toolset_utils = $this->dic->make( \Toolset\DynamicSources\Utils\Toolset::class );
		if ( $toolset_utils->is_views_enabled() ) {
			$internals_args = array(
				':view_get_instance' => array(
					'\WPV_View_Embedded',
					'get_instance',
				),
				':content_template_get_instance' => array(
					'\WPV_Content_Template_Embedded',
					'get_instance',
				),
			);

			$toolset_views_integration_internals = $this->dic->make(
				\Toolset\DynamicSources\Integrations\Views\Internals::class,
				$internals_args
			);

			$args = array(
				':content_template_post_type' => \WPV_Content_Template_Embedded::POST_TYPE,
				':wpa_helper_post_type' => null,
				':integration_internals' => $toolset_views_integration_internals,
			);

			if ( class_exists( BlockEditorWPA::class ) ) {
				$args[':wpa_helper_post_type'] = BlockEditorWPA::WPA_HELPER_POST_TYPE;
			}

			$toolset_views_integration = $this->dic->make(
				\Toolset\DynamicSources\Integrations\Views::class,
				$args
			);

			$toolset_views_integration->initialize();
		}
	}

	public function initialize_rest() {
		$rest_api = $this->dic->make( Rest\Main::class );
		$rest_api->initialize();
	}

	public function initialize_sources() {
		$post_sources = array(
			'PostTitle',
			'PostTitleWithLink',
			'PostURL',
			'PostContent',
			'PostExcerpt',
			'PostID',
			'PostName',
			'PostDate',
			'PostDateGMT',
			'PostDateModified',
			'PostDateModifiedGMT',
			'PostType',
			'PostStatus',
			// 'PostFields',
			'PostTaxonomies',
			'PostTaxonomiesRich',
			'AuthorID',
			'AuthorName',
			'AuthorPostsLink',
			'AuthorPostsUrl',
			'AuthorProfilePicture',
			'AuthorPosts',
			'AuthorFirstName',
			'AuthorLastName',
			'CommentsNumber',
			'CommentsStatus',
			'MediaFeaturedImageData',
		);

		/**
		 * Filters the set of "Post" dynamic sources.
		 *
		 * @param array $post_sources
		 */
		$post_sources = apply_filters( 'toolset/dynamic_sources/filters/post_sources', $post_sources );

		$other_sources = array(
			'SiteTagline',
		);

		/**
		 * Filters the set of "Other" dynamic sources.
		 *
		 * @param array $other_sources
		 */
		$other_sources = apply_filters( 'toolset/dynamic_sources/filters/other_sources', $other_sources );

		foreach ( array_merge( $post_sources, $other_sources ) as $source ) {
			$sourceClass = '\Toolset\DynamicSources\Sources\\' . $source;
			$this->add_source_for_registration( $this->dic->make( $sourceClass ) );
		}

		// Dynamic Sources Cache.
		$dynamic_sources_cache = $this->dic->make(
			'\Toolset\DynamicSources\Cache',
			array(
				':sources' => array(
					'post' => array(
						'post_relevant' => true,
						'sources' => $post_sources
					),
					'other' => array(
						'post_relevant' => false,
						'sources' => $other_sources
					),
				),
			)
		);
		$dynamic_sources_cache->initialize();
	}

	/**
	 * Registers the Dynamic Sources.
	 *
	 * @param null|int $view_id Needed when getting potential sources inside a View, to get View settings.
	 */
	public function register_sources( $view_id = null ) {
		$post_id = get_the_ID();
		$post_type = get_post_type();

		// When the editor autosaves, the post type is "revision", so we need to find the parent of the revision post
		// and get its post ID.
		if ( 'revision' === $post_type ) {
			$post_id = wp_get_post_parent_id( $post_id );
			$post_type = get_post_type( $post_id );
		}

		if (
			! $post_type &&
			defined( 'REST_REQUEST' ) &&
			REST_REQUEST

		) {
			if (
				isset( $_GET['post'] ) &&
				is_numeric( $_GET['post'] )
			) {
				$post_id = absint( $_GET[ 'post' ] );
				$post_type = get_post_type( $post_id );
			}

			if ( isset( $_GET['post-type'] ) ) {
				$post_type = explode( ',', sanitize_text_field( $_GET['post-type'] ) );
			}
		}

		$post_type = apply_filters( 'toolset/dynamic_sources/filters/post_type_for_source_context', $post_type, $post_id );

		if ( ! $post_type ) {
			$post_type = array();
		}

		// If the Dynamic Sources are already registered for this post type, then load the registered data.
		if ( $this->maybe_fetch_cached_dynamic_sources_for_post_type( $post_type ) ) {
			return;
		}

		// Get the initial context, in which everything happens.
		$this->source_context = $this->sources_registration_factory->build_source_context( $post_type, $view_id );

		if ( null === $this->source_context ) {
			return;
		}

		// The "Featured Image" source needs to be excluded for post types that are not supporting a thumbnail (featured image).
		$this->sources_for_registration = $this->sources_registration_factory->maybe_exclude_featured_image_source_from_registration( $post_type, $this->sources_for_registration );

		// Based on this context, get all sources of posts which we can use.
		$this->post_providers = $this->sources_registration_factory->register_post_providers( $this->source_context );

		// Register data sources that can be used with available post providers.
		$this->register_data_sources( $this->post_providers );

		// A set of Dynamic Sources were registered for a new post type, so let's cache those values for future use.
		$this->cache_dynamic_sources_for_post_type( $post_type );
	}

	/**
	 * Retrieves from cache the registered Dynamic Sources for a given post type.
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */
	private function maybe_fetch_cached_dynamic_sources_for_post_type( $post_type ) {
		if ( is_array( $post_type ) ) {
			$post_type = implode( '_', $post_type );
		}

		if ( isset( $this->registered_dynamic_sources_repo[ $post_type ] ) ) {
			$this->source_context = $this->registered_dynamic_sources_repo[ $post_type ]['source_context'];
			$this->source_storage = $this->registered_dynamic_sources_repo[ $post_type ]['source_storage'];
			$this->post_providers = $this->registered_dynamic_sources_repo[ $post_type ]['source_storage']->get_post_providers();

			return true;
		}

		return false;
	}

	/**
	 * Caches the registered Dynamic Sources for a given post type.
	 *
	 * @param $post_type
	 */
	private function cache_dynamic_sources_for_post_type( $post_type ) {
		if ( is_array( $post_type ) ) {
			$post_type = implode( '_', $post_type );
		}

		$this->registered_dynamic_sources_repo[ $post_type ] = array(
			'source_context' => $this->source_context,
			'source_storage' => $this->source_storage,
		);
	}

	/**
	 * @param PostProvider[] $post_providers
	 */
	private function register_data_sources( $post_providers ) {
		/**
		 * Filters the the Dynamic Sources offered.
		 *
		 * @param array       $sources   The dynamic sources
		 * @param null|string $post_type The post type of the post with ID coming from the URL parameters.
		 */
		$this->sources_for_registration = apply_filters(
			'toolset/dynamic_sources/filters/register_sources',
			$this->sources_for_registration,
			$post_providers
		);

		$this->source_storage = new SourceStorage( $post_providers );

		foreach( $this->sources_for_registration as $source_for_registration ) {
			$this->register_source( $source_for_registration );
		}
	}

	/**
	 * Gets source content
	 *
	 * @param string     $post_provider Post provider
	 * @param int        $post Post ID
	 * @param string     $source Source ID
	 * @param string     $field Field ID
	 * @param array|null $extra_attributes Extra attributes comming from [tb-dynamic] shortcode
	 */
	public function get_source_content(
		/** @noinspection PhpUnusedParameterInspection */ $content,
		$post_provider,
		$post,
		$source,
		$field = null,
		$extra_attributes = null
	) {
		if ( ! $post ) {
			return '';
		}

		// If it is a custom post provider and it is missing, update.
		// It is needed because Views can have also blocks using Custom Posts as providers, and it is the best way to update it... when needed.
		$this->maybe_update_missing_custom_post_provider( $post_provider );

		if (
			$post_provider &&
			isset( $this->post_providers[ $post_provider ] ) &&
			$this->post_providers[ $post_provider ] instanceof PostProvider
		) {
			$post = $this->post_providers[ $post_provider ]->get_post( $post );
		} else {
			return '';
		}

		if ( ! $post ) {
			return '';
		}

		$this->switch_to_post( $post );

		$source = $this->create_source( $source, $this->post_providers[ $post_provider ] );

		// Even if the source returns no content, the execution of the method needs to continue in order to restore the
		// previously set "current post".
		$content = ! $source || ! $source instanceof Sources\Source ?
			'' :
			$source->get_content( $field, $extra_attributes );

		$this->restore_current_post();

		return $content;
	}

	public function get_source_fields(
		/** @noinspection PhpUnusedParameterInspection */ $fields, $post, $source, $post_provider
	) {
		if (
			! $post_provider ||
			! isset( $this->post_providers[ $post_provider ] ) ||
			! ( $this->post_providers[ $post_provider ] instanceof PostProvider )
		) {
			return '';
		}

		$this->switch_to_post( $post );

		$source = $this->create_source( $source, $this->post_providers[ $post_provider ] );

		if ( ! $source || ! $source instanceof Sources\Source ) {
			return '';
		}

		$fields = $source->get_fields();

		$this->restore_current_post();

		return $fields;
	}

	/**
	 * Gets the post providers for internal use.
	 *
	 * Callback for the 'toolset/dynamic_sources/filters/get_post_providers' filter.
	 *
	 * @return PostProvider[]
	 */
	public function get_post_providers() {
		return $this->post_providers;
	}

	/**
	 * Gets the post providers, ready for the UI Select control of the Dynamic panel.
	 *
	 * Callback for the 'toolset/dynamic_sources/filters/get_post_providers_for_select' filter.
	 *
	 * @return array
	 */
	public function get_post_providers_for_select() {
		$post_providers = array();
		$post_provider_labels = array();

		// Make an array of duplicate labels.
		foreach ( $this->post_providers as $post_provider ) {
			$label_for_duplicate_check = strtolower( trim( $post_provider->get_label() ) );
			$post_provider_labels[] = $label_for_duplicate_check;
		}
		$duplicate_labels = array_diff_assoc( $post_provider_labels, array_unique( $post_provider_labels ) );

		foreach ( $this->post_providers as $post_provider ) {
			$label_for_duplicate_check = strtolower( trim( $post_provider->get_label() ) );
			$has_duplicate_label = in_array( $label_for_duplicate_check, $duplicate_labels, true );
			$post_providers[] = array(
				'value' => $post_provider->get_unique_slug(),
				'label' => $post_provider->get_label( $has_duplicate_label ),
			);
		}

		return $post_providers;
	}

	/**
	 * Gets the registered sources grouped, ready for the UI Select control of the Dynamic panel.
	 *
	 * Callback for the 'toolset/dynamic_sources/filters/get_grouped_sources' filter.
	 *
	 * @return array
	 */
	public function get_grouped_sources() {
		$output_sources = array();

		foreach ( $this->post_providers as $post_provider ) {
			$output_sources_for_post_provider = array();

			$sources = array_map(
				function( Source $post_source ) use ( $post_provider ) {
					return $this->get_source_info( $post_source, $post_provider );
				},
				$this->source_storage->get_sources_for_post_provider( $post_provider )
			);

			$groups = $this->get_groups();

			foreach ( $sources as $source ) {
				if (
					$source['instance']::HAS_FIELDS &&
					empty( $source['fields'] )
				) {
					continue;
				}

				if ( ! isset( $output_sources_for_post_provider[ $source['group'] ] ) ) {
					$output_sources_for_post_provider[ $source['group'] ] = array(
						'label'   => isset( $groups[ $source['group'] ] ) ?
							$groups[ $source['group'] ] :
							$groups[ self::OTHER_GROUP ],
						'id'      => $source['group'],
						'options' => array(),
					);
				}

				$output_sources_for_post_provider[ $source['group'] ]['options'][] = array(
					'label' => $source['title'],
					'value' => $source['name'],
					'group' => $source['group'],
					'categories' => $source['categories'],
					'fields' => $source['fields'],
				);
			}

			$output_sources[ $post_provider->get_unique_slug() ] = array_values( $output_sources_for_post_provider );
		}

		return $output_sources;
	}

	private function get_groups() {
		$groups = array(
			self::POST_GROUP => __( 'Post', 'wpv-views' ),
			self::AUTHOR_GROUP => __( 'Author', 'wpv-views' ),
			self::SITE_GROUP => __( 'Site', 'wpv-views' ),
			self::MEDIA_GROUP => __( 'Media', 'wpv-views' ),
			self::COMMENTS_GROUP => __( 'Comments', 'wpv-views' ),
			self::OTHER_GROUP => __( 'Other', 'wpv-views' ),
		);

		/**
		 * Filters the groups of the Dynamic Sources offered.
		 *
		 * @param array $groups The groups of Dynamic Sources.
		 */
		$groups = apply_filters( 'toolset/dynamic_sources/filters/groups', $groups );

		return  $groups;
	}

	public function dynamic_container_shortcode_render( $attributes, $content ) {
		if ( $this->maybe_prevent_shortcode_rendering() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'provider' => '',
				'post' => 'current',
				'source' => '',
				'field' => '',
				'default-value' => null,
			),
			$attributes
		);

		// strings to array
		$providers = explode( self::VALUE_SEPARATOR, $atts['provider'] );
		$sources = explode( self::VALUE_SEPARATOR, $atts['source'] );
		$fields = explode( self::VALUE_SEPARATOR, $atts['field'] );
		$default_value = explode( self::VALUE_SEPARATOR, $atts['default-value'] );

		// loop over all required sources
		for( $i = 0; $i < count( $providers ); $i++ ) {
			$has_content_attributes = array(
				'provider' => $providers[$i],
				'source' => $sources[$i],
				'field' => $fields[$i],
				'default-value' => isset( $default_value[ $i ] ) ? $default_value[ $i ] : null,
			);

			$has_content = $this->get_shortcode_content( $has_content_attributes );

			if( empty( $has_content ) && ! is_numeric( $has_content ) ) {
				// one required sources has no value... means no output for this block
				return '';
			}
		}

		// all required dynamic sources have content, proceed...
		$content = do_shortcode( $content );

		if( empty( $content ) && ! is_numeric( $content ) ) {
			// empty inner content, means theres probably some misconfiguration on the block
			// because the container should have the same parameters as the inner shortcode
			return '';
		}

		// content available
		return $content;
	}

	public function dynamic_shortcode_render( $attributes ) {
		if ( $this->maybe_prevent_shortcode_rendering() ) {
			return '';
		}

		return $this->get_shortcode_content( $attributes );
	}

	private function get_shortcode_content( $attributes ) {
		$atts = shortcode_atts(
			array(
				'provider' => IdentityPost::UNIQUE_SLUG,
				'post' => 'current',
				'source' => '',
				'field' => '',
				'force-string' => false,
				'default-value' => null,
			),
			$attributes
		);

		$post_provider = sanitize_text_field( $atts['provider'] );
		$post = sanitize_text_field( $atts['post'] );
		$source = sanitize_text_field( $atts[ 'source' ] );
		$field = sanitize_text_field( $atts['field'] );
		$default_value = sanitize_text_field( $atts['default-value'] );

		if ( 'current' === $post ) {
			$post = get_the_ID();
		} else {
			$post = intval( $post );
		}

		/**
		 * Registers the Dynamic Sources if needed.
		 */
		do_action( 'toolset/dynamic_sources/actions/register_sources' );

		/**
		 * Filters the post provider
		 *
		 * @param string $post_provider e.g. `custom_post_type|post|65`
		 */
		$post_provider = apply_filters( 'toolset/dynamic_sources/filters/shortcode_post_provider', $post_provider );

		$post = apply_filters( 'toolset/dynamic_sources/filters/shortcode_post', $post, $post_provider, $source, $field );

		$output = $this->get_source_content( '', $post_provider, $post, $source, $field, $attributes );

		if ( $atts[ 'force-string' ] && is_array( $output ) ) {
			if ( 'first' === $atts[ 'force-string' ] ) {
				$output = reset( $output );
			}
			if ( is_array( $output ) ) {
				$output = implode( ',', $output );
			}
		}
		if ( ! $output && null !== $default_value ) {
			return $default_value;
		}
		return $output;
	}

	private function register_source( Sources\Source $source ) {
		$this->source_storage->add_source( $source );
	}


	public function add_source_for_registration( $source ) {
		$this->sources_for_registration[] = $source;
	}


	private function switch_to_post( $post_id ) {
		// If is already switched, or is the same post, return.
		if ( get_the_ID() === $post_id ) {
			$this->switched_post_data[] = false;
			return;
		}

		$this->switched_post_data[] = [
			'switched_id' => $post_id,
			'original_id' => get_the_ID(), // Note, it can be false if the global isn't set
			'original_post' => get_post(),
		];

		global $post;

		$post = get_post( $post_id );

		setup_postdata( $post );
	}


	private function restore_current_post() {
		$data = array_pop( $this->switched_post_data );

		// If not switched, return.
		if ( ! $data ) {
			return;
		}

		global $post;

		// It was switched from an empty global post, restore this state and unset the global post
		if ( false === $data['original_id'] ) {
			unset( $post );
			return;
		}

		$post = $data['original_post'];

		setup_postdata( $post );
	}

	private function create_source( $source_name, PostProvider $post_provider ) {
		$source_info = $this->get_source_info( $source_name, $post_provider );

		if ( ! $source_info ) {
			return null;
		}

		return $source_info['instance'];
	}

	/**
	 * @param string|Source $source_name
	 *
	 * @return array|null
	 */
	private function get_source_info( $source_name, PostProvider $post_provider ) {
		if ( $source_name instanceof Source ) {
			$source = $source_name;
		} else {
			$source = $this->source_storage->get_source( $source_name );
		}

		if ( null === $source ) {
			return null;
		}

		$source->set_post_provider( $post_provider );

		return array(
			'instance' => $source,
			'name' => $source->get_name(),
			'title' => $source->get_title(),
			'group' => $source->get_group(),
			'categories' => $source->get_categories(),
			'fields' => $source->get_fields(),
		);
	}

	/**
	 * This will render our shortcodes before any filtering, because WordPress has some very strict rules
	 * on html attributes.
	 *
	 * THIS IS MORE A START OF A PROPER FIX AND NEEDS TO BE WORKED OUT
	 * @TODO PROPER IMPLEMENTATION
	 *
	 * @param $content
	 *
	 * @return string|string[]|null
	 *
	 */
	public function shortcode_render( $content ) {
		return preg_replace_callback( '#(\[tb-dynamic\s.*?\])#', function( $shortcode ) {
			return do_shortcode( $shortcode[0] );
		}, $content );
	}

	public function register_localization_data() {
		$block_script_dependencies = array( 'wp-blocks', 'wp-editor' );
		wp_enqueue_script( self::TOOLSET_DYNAMIC_SOURCES_SCRIPT_HANDLE, plugins_url( '../build/index.js', __FILE__ ), $block_script_dependencies );
		wp_enqueue_style( self::TOOLSET_DYNAMIC_SOURCES_STYLE_HANDLE, plugins_url( '../build/css/index.css', __FILE__ ) );

		$dynamic_sources_data = apply_filters( 'toolset/dynamic_sources/filters/get_dynamic_sources_data', [] );

		wp_localize_script(
			self::TOOLSET_DYNAMIC_SOURCES_SCRIPT_HANDLE,
			self::TOOLSET_DYNAMIC_SOURCES_JS_OBJECT_NAME,
			$dynamic_sources_data
		);
	}

	public function get_dynamic_sources_data( $dynamic_sources_data ) {
		return array_merge(
			$dynamic_sources_data,
			array(
				'postProviders' => apply_filters( 'toolset/dynamic_sources/filters/get_post_providers_for_select', array() ),
				'dynamicSources' => apply_filters( 'toolset/dynamic_sources/filters/get_grouped_sources', array() ),
				'dynamicSourcesStore' => self::TOOLSET_BLOCKS_DYNAMIC_SOURCES_STORE,
				'cache' => apply_filters( 'toolset/dynamic_sources/filters/cache', array(), get_the_ID() ),
			)
		);
	}

	/**
	 * Updates the list of post providers
	 *
	 * @param string $provider
	 */
	private function maybe_update_missing_custom_post_provider( $provider ) {
		if ( ! isset( $this->post_providers[ $provider ] ) && preg_match( self::CUSTOM_POST_TYPE_REGEXP, '"' . $provider . '"' ) ) {
			$data = explode( '|', $provider );
			$custom_post = get_post( $data[ 2 ] );
			if ( $custom_post ) {
				$custom_post_provider = new CustomPost( $custom_post->post_type, $custom_post->ID );
				$this->post_providers[ $custom_post_provider->get_unique_slug() ] = $custom_post_provider;
			}
		}
	}

	/**
	 * Decides whether to prevent the dynamic shortcodes rendering for certain requests.
	 *
	 * @return bool
	 */
	private function maybe_prevent_shortcode_rendering() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// Targeting the "http://my-server.test/wp-json/wp/v2/pages?per_page=100&exclude=2&parent_exclude=2&orderby=menu_order&order=asc&context=edit&_locale=user"
			// REST API requesting happening on the new post page for hierarchical post types, that is used to populate the
			// "Parent Post/Page" dropdown.
			// Can be removed once https://github.com/WordPress/gutenberg/issues/17160 (duplicates https://github.com/WordPress/gutenberg/issues/13618)
			// is resolved.
			if (
				boolval( sanitize_text_field( toolset_getget( 'per_page', false ) ) ) &&
				boolval( sanitize_text_field( toolset_getget( 'exclude', false ) ) ) &&
				boolval( sanitize_text_field( toolset_getget( 'parent_exclude', false ) ) ) &&
				boolval( sanitize_text_field( toolset_getget( 'orderby', false ) ) ) &&
				boolval( sanitize_text_field( toolset_getget( 'order', false ) ) ) &&
				boolval( sanitize_text_field( toolset_getget( 'context', false ) ) )
			) {
				return true;
			}
		}

		return false;
	}
}
