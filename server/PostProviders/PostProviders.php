<?php

namespace Toolset\DynamicSources\PostProviders;

use Toolset\DynamicSources\DynamicSources;

/**
 * Class PostProviders
 *
 * Handles the post providers related stuff in Dynamic Sources API.
 *
 * @package Toolset\DynamicSources\PostProviders
 */
class PostProviders {
	/** @var CustomPostFactory */
	private $custom_post_factory;

	/**
	 * PostProviders constructor.
	 *
	 * @param CustomPostFactory $custom_post_factory
	 */
	public function __construct( CustomPostFactory $custom_post_factory ) {
		$this->custom_post_factory = $custom_post_factory;
	}


	public function initialize() {
		// This filter needs to applied very late (as per Luis S. sayings). This filter was moved here and it was initially added
		// for the fix of toolsetblocks-186.
		add_filter( 'toolset/dynamic_sources/filters/register_post_providers', array( $this, 'set_custom_post_provider' ), 10000 );
	}

	/**
	 * Adds a CustomPost provider to the last element
	 *
	 * @param array $providers
	 *
	 * @return array
	 */
	public function set_custom_post_provider( $providers, $content = null ) {
		global $post;

		// It is a special case, for searching different posts
		$custom_post = $this->custom_post_factory->create_custom_post();
		$providers[ $custom_post->get_unique_slug() ] = $custom_post;

		// Gets custom posts from content
		if ( isset( $post ) && isset( $post->post_content ) || $content ) {
			if ( ! $content ) {
				$content = $post->post_content;
			}

			$providers = $this->get_custom_post_providers_from_content( $providers, $content );

			$providers = $this->get_custom_post_providers_from_reusable_blocks( $providers, $content );
		}

		return $providers;
	}

	/**
	 * Parses the content and looks for usage of custom post dynamic sources so as to add those to the list of
	 * offered post providers.
	 *
	 * @param array  $providers The set of "filtered" post providers.
	 * @param string $content   The content to be parsed.
	 *
	 * @return array
	 */
	private function get_custom_post_providers_from_content( $providers, $content ) {
		preg_match_all( DynamicSources::CUSTOM_POST_TYPE_REGEXP, $content, $custom_posts );
		if ( isset( $custom_posts[ 1 ] ) ) {
			foreach ( $custom_posts[ 1 ] as $custom_post_provider ) {
				list( $slug, $post_type, $post_id ) = explode( '|', $custom_post_provider );
				$custom_post = $this->custom_post_factory->create_custom_post( $post_type, $post_id );
				$providers[ $custom_post->get_unique_slug() ] = $custom_post;
			}
		}

		return $providers;
	}

	/**
	 * Parses the content and looks for reusable blocks in order to parse their content to look for usage of custom post
	 * dynamic sources so as to add those to the list of offered post providers.
	 *
	 * @param array  $providers The set of "filtered" post providers.
	 * @param string $content   The content to be parsed.
	 *
	 * @return array
	 */
	private function get_custom_post_providers_from_reusable_blocks( $providers, $content ) {
		$blocks = parse_blocks( $content );
		foreach( $blocks as $block ) {
			$attributes = isset( $block['attrs'] ) ? $block['attrs'] : array();

			if ( empty( $attributes['ref'] ) ) {
				continue;
			}

			$reusable_block = get_post( $attributes['ref'] );

			if (
				! $reusable_block ||
				'wp_block' !== $reusable_block->post_type ||
				'publish' !== $reusable_block->post_status ||
				! empty( $reusable_block->post_password )
			) {
				continue;
			}

			$providers = $this->get_custom_post_providers_from_content( $providers, $reusable_block->post_content );
		}
		return $providers;
	}
}
