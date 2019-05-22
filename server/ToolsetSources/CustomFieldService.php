<?php

namespace Toolset\DynamicSources\ToolsetSources;


use Toolset\DynamicSources\DynamicSources;

/**
 * Layer for communicating with Toolset Common regarding custom fields and field groups.
 */
class CustomFieldService {


	/**
	 * For a given post type, retrieve slugs of custom field groups that should be displayed on it.
	 *
	 * @param string $post_type_slug
	 * @return string[]
	 */
	public function get_group_slugs_by_type( $post_type_slug ) {
		/*$field_groups = apply_filters( 'types_query_groups', [], [
			'domain' => 'posts',
			'is_active' => true,
			'purpose' => '*',

		] )*/

		$field_groups = \Toolset_Field_Group_Post_Factory::get_instance()
			->get_groups_by_post_type( $post_type_slug );

		return array_map( function ( \Toolset_Field_Group_Post $field_group ) {
			return $field_group->get_slug();
		}, $field_groups );
	}


	private function is_field_type_supported( $field_type_slug ) {
		$supported_types = array(
			'textfield',
			'phone',
			'textarea',
			'checkbox',
			'checkboxes',
			'colorpicker',
			'select',
			'numeric',
			'email',
			'embed',
			'google_address',
			'wysiwyg',
			'radio',
			'url',
			'audio',
			'video',
			'image',
			'skype',
			'date',
			'file',
		);

		return in_array( $field_type_slug, $supported_types );
	}


	/**
	 * For a given slug of the custom field group, instantiate its model.
	 *
	 * @param string $field_group_slug Slug of an existing group.
	 * @return FieldGroupModel
	 */
	public function create_group_model( $field_group_slug ) {
		$field_group = \Toolset_Field_Group_Post_Factory::get_instance()->load_field_group( $field_group_slug );

		if( null === $field_group ) {
			return null;
		}

		$elligible_field_definitions = array_filter(
			$field_group->get_field_definitions(),
			function( \Toolset_Field_Definition $field_definition ) {
				if( ! $this->is_field_type_supported( $field_definition->get_type()->get_slug() ) ) {
					return false;
				}

				return true;
			}
		);

		$field_models = array_values(
			array_map(
				function( \Toolset_Field_Definition $field_definition ) {
					$definition = $field_definition->get_definition_array();
					return new FieldModel(
						$field_definition->get_slug(),
						$field_definition->get_name(),
						$field_definition->get_type()->get_slug(),
						$this->get_categories_for_field_type( $field_definition->get_type()->get_slug() ),
						isset( $definition[ 'data' ][ 'options' ] ) ? $definition[ 'data' ][ 'options' ] : null,
						$field_definition->get_is_repetitive()
					);
				},
				$elligible_field_definitions
			)
		);

		if( count( $field_models ) === 0 ) {
			return null;
		}

		return new FieldGroupModel(
			$field_group_slug, $field_group->get_name(), $field_models
		);
	}

	/**
	 * @param FieldModel $field
	 * @param array $attributes
	 *
	 * @return FieldInstanceModel
	 */
	public function get_field_instance_for_current_post( FieldModel $field, $attributes = null ) {
		return new FieldInstanceModel( $field, $attributes );
	}

	/**
	 * @param $field_type
	 *
	 * @return array
	 */
	public function get_categories_for_field_type( $field_type ) {
		$text = [ DynamicSources::TEXT_CATEGORY ];
		$number = array_merge( $text, [ DynamicSources::NUMBER_CATEGORY ] );
		$url = array_merge( $text, [ DynamicSources::URL_CATEGORY ] );
		switch ( $field_type ) {
			case 'textfield':
			case 'email':
			case 'radio':
			case 'select':
			case 'checkbox':
			case 'checkboxes':
			case 'embed':
			case 'phone':
			case 'textarea':
			case 'wysiwyg':
			case 'colorpicker':
				return $text;
			case 'date':
				return array_merge( $text, [ DynamicSources::DATE_CATEGORY ] );
			case 'numeric':
				return $number;
			case 'url':
				return $url;
			case 'image':
				return array_merge( $url, [ DynamicSources::IMAGE_CATEGORY ] );
			case 'audio':
				return array_merge( $url, [ DynamicSources::AUDIO_CATEGORY ] );
			case 'video':
				return array_merge( $url, [ DynamicSources::VIDEO_CATEGORY ] );
			default:
				return $text;
		}
	}

}
