<?php

namespace Toolset\DynamicSources;

use Toolset\DynamicSources\PostProviders\IdentityPostFactory;
use Toolset\DynamicSources\SourceContext\SourceContext;
use Toolset\DynamicSources\SourceContext\PostTypeSourceContextFactory;

class Registration {
	/** @var IdentityPostFactory */
	private $identity_post_factory;

	/**
	 * @var PostTypeSourceContextFactory
	 */
	private $post_type_source_context_factory;

	/**
	 * Registration constructor.
	 *
	 * @param IdentityPostFactory          $identity_post_factory
	 * @param PostTypeSourceContextFactory $post_type_source_context_factory
	 */
	public function __construct( IdentityPostFactory $identity_post_factory, PostTypeSourceContextFactory $post_type_source_context_factory ) {
		$this->identity_post_factory = $identity_post_factory;
		$this->post_type_source_context_factory = $post_type_source_context_factory;
	}

	/**
	 * Excludes the Featured Image source from the registered dynamic sources.
	 *
	 * There might be post types that do not support a thumbnail (featured image) thus for those post types this source
	 * will need to be excluded and not show up in the offered dynamic sources.
	 *
	 * @param string|array $post_types
	 * @param array        $sources_for_registration
	 *
	 * @return array
	 */
	public function maybe_exclude_featured_image_source_from_registration( $post_types, $sources_for_registration ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		$should_exclude_featured_image = true;
		foreach ( $post_types as $post_type ) {
			// If there is at least one post type in the post providers that supports thumbnail (featured image), don't
			// exclude the source.
			if ( post_type_supports( $post_type, 'thumbnail' ) ) {
				$should_exclude_featured_image  = false;
				break;
			}
		}

		if ( ! $should_exclude_featured_image ) {
			return $sources_for_registration;
		}

		return array_filter(
			$sources_for_registration,
			function( $item ) {
				if ( $item instanceof \Toolset\DynamicSources\Sources\MediaFeaturedImageData ) {
					return false;
				}
				return true;
			}
		);
	}

	/**
	 * Register all post providers available within the given source context.
	 *
	 * @param SourceContext $source_context
	 *
	 * @return PostProvider[]
	 */
	public function register_post_providers( SourceContext $source_context ) {
		$identity_post = $this->identity_post_factory->create_identity_post( $source_context->get_post_types() );
		$post_providers = array_filter(
			apply_filters(
				'toolset/dynamic_sources/filters/register_post_providers',
				array( $identity_post->get_unique_slug() => $identity_post ),
				$source_context
			),
			function( $post_provider ) {
				return $post_provider instanceof PostProvider;
			}
		);

		return $post_providers;
	}

	/**
	 * @param string[]|string $post_type
	 * @param null|int $view_id
	 *
	 * @return SourceContext
	 */
	public function build_source_context( $post_type, $view_id = null ) {
		if ( $view_id ) {
			$source_context = $this->post_type_source_context_factory
				->create_view_source_context( $post_type, $view_id );
		} else {
			/**
			 * Filter that allows altering the SourceContext object before it is used.
			 *
			 * @param SourceContext
			 *
			 * @return SourceContext
			 */
			$source_context = apply_filters(
				'toolset/dynamic_sources/filters/source_context',
				$this->post_type_source_context_factory->create_post_type_source_context( $post_type )
			);
		}

		if( ! $source_context instanceof SourceContext ) {
			throw new \InvalidArgumentException();
		}

		return $source_context;
	}
}
