<?php

namespace Toolset\DynamicSources\ToolsetSources;


use Toolset\DynamicSources\DynamicSources;
use OTGS\Toolset\Common\PublicAPI as toolsetAPI;

/**
 * Layer for communicating with Toolset Common regarding custom fields and field groups.
 */
class CustomFieldService {

	/**
	 * CustomFieldService constructor.
	 */
	public function __construct() {
		// This filter is only present to make TCES work without DS.
		// It prevents sites, which only use Toolset Forms, from the need to load DS at all.
		add_filter( 'tces_get_categories_for_field_type', array( $this, 'filter_get_categories_for_field_type' ), 10, 2 );
	}

	/**
	 * For a given post type, retrieve slugs of custom field groups that should be displayed on it.
	 *
	 * @param string $post_type_slug
	 * @return string[]
	 */
	public function get_group_slugs_by_type( $post_type_slug ) {
		$field_groups = toolset_get_field_groups( [
			'domain' => 'posts',
			'is_active' => true,
			'purpose' => '*',
			'assigned_to_post_type' => $post_type_slug
		] );

		// If $post_type_slug is the slug of an RFG, the field group will be loaded using an alternative method.
		// The RFG field group has "hidden" status, so this is why "get_groups_by_post_type" cannot load it.
		$maybe_rfg_field_group = toolset_get_field_group( $post_type_slug, toolsetAPI\ElementDomain\POSTS );
		if (
			$maybe_rfg_field_group
		 	&& toolsetAPI\CustomFieldGroupPurpose\FOR_REPEATING_FIELD_GROUP === $maybe_rfg_field_group->get_purpose()
		) {
			// ... the $post_type_slug is the slug of an RFG, so let's append this to the $fields_groups array.
 			$field_groups[] = $maybe_rfg_field_group;
		}

		return array_map( function ( toolsetAPI\CustomFieldGroup $field_group ) {
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
		$field_group = toolset_get_field_group( $field_group_slug, toolsetAPI\ElementDomain\POSTS );

		if( null === $field_group ) {
			return null;
		}

		$field_group_fields = array(
			'fields' => array(),
			'repeating_groups' => array(),
		);

		foreach ( $field_group->get_field_definitions() as $field_definition ) {
			if ( null !== $field_definition ) {
				$field_group_fields['fields'][] = $field_definition;
			} else {
				/** @var toolsetAPI\CustomFieldGroup|null $repeatable_group */
				$repeatable_group = apply_filters( 'types_get_rfg_from_prexied_string', null, $field_definition->get_slug() );

				if ( $repeatable_group ) {
					if ( ! empty( $repeatable_group->get_field_definitions() ) ) {
						$field_group_fields['repeating_groups'][] = $repeatable_group;
					}
				}
			}
		}

		$elligible_field_definitions = array_filter(
			$field_group_fields['fields'],
			function( toolsetAPI\CustomFieldDefinition $field_definition ) {
				if( ! $this->is_field_type_supported( $field_definition->get_type_slug() ) ) {
					return false;
				}

				return true;
			}
		);

		$field_models = array_values(
			array_map(
				function( toolsetAPI\CustomFieldDefinition $field_definition ) {

					// Workaround because raw definition array has been used here but we
					// cannot expose it through the public API in this form. And providing
					// it in a structured way will require quite a lot of work, so for now,
					// we just do this little hack.
					if ( is_subclass_of( $field_definition, 'Toolset_Field_Definition' ) ) {
						/** @var \Toolset_Field_Definition $field_definition */
						$definition_array = $field_definition->get_definition_array();
						$raw_field_options = isset( $definition_array[ 'data' ][ 'options' ] ) ? $definition_array[ 'data' ][ 'options' ] : null;
					} else {
						$raw_field_options = null;
					}

					return new FieldModel(
						$field_definition->get_slug(),
						$field_definition->get_name(),
						$field_definition->get_type_slug(),
						$this->get_categories_for_field_type( $field_definition->get_type_slug() ),
						$raw_field_options,
						$field_definition->is_repeatable()
					);
				},
				$elligible_field_definitions
			)
		);

		$rfgs_as_field_models = array_values(
			array_map(
				function( toolsetAPI\CustomFieldGroup $rfg_definition ) {
					$field_type = 'rfg';
					return new FieldModel(
						$rfg_definition->get_slug(),
						$rfg_definition->get_display_name(),
						$field_type,
						$this->get_categories_for_field_type( $field_type ),
						null,
						false
					);
				},
				$field_group_fields['repeating_groups']
			)
		);

		if (
			count( $field_models ) === 0 &&
			count( $rfgs_as_field_models ) === 0
		) {
			return null;
		}

		return new FieldGroupModel(
			$field_group_slug,
			$field_group->get_display_name(),
			array_merge( $field_models, $rfgs_as_field_models ),
			toolsetAPI\CustomFieldGroupPurpose\FOR_REPEATING_FIELD_GROUP === $field_group->get_purpose()
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
	 * Callback function on TCES filter "tces_get_categories_for_field_type".
	 *
	 * @param $incoming_categories Ignored.
	 * @param $field_type
	 *
	 * @return array
	 */
	public function filter_get_categories_for_field_type( $incoming_categories, $field_type ) {
		$categories = $this->get_categories_for_field_type( $field_type );

		return $categories;
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
			case 'date':
				return array_merge( $text, [ DynamicSources::DATE_CATEGORY ] );
			case 'numeric':
				return $number;
			case 'url':
				return array_merge( $url, [ DynamicSources::EMBED_CATEGORY ] );
			case 'image':
				return array_merge( $url, [ DynamicSources::IMAGE_CATEGORY ] );
			case 'audio':
				return array_merge( $url, [ DynamicSources::AUDIO_CATEGORY ] );
			case 'video':
				return array_merge( $url, [ DynamicSources::VIDEO_CATEGORY ] );
			case 'rfg':
				return [
					DynamicSources::VIDEO_CATEGORY,
					DynamicSources::URL_CATEGORY,
					DynamicSources::NUMBER_CATEGORY,
					DynamicSources::IMAGE_CATEGORY,
					DynamicSources::HTML_CATEGORY,
					DynamicSources::DATE_CATEGORY,
					DynamicSources::AUDIO_CATEGORY,
					DynamicSources::TEXT_CATEGORY,
				];
			case 'embed':
			case 'textfield':
				return [
					DynamicSources::TEXT_CATEGORY,
					DynamicSources::EMBED_CATEGORY,
				];
			case 'email':
			case 'radio':
			case 'select':
			case 'checkbox':
			case 'checkboxes':
			case 'phone':
			case 'textarea':
			case 'wysiwyg':
			case 'colorpicker':
			default:
				return $text;
		}
	}

}
