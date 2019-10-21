<?php

namespace Toolset\DynamicSources\ToolsetSources;


use Toolset\DynamicSources\DynamicSources;

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
		/*$field_groups = apply_filters( 'types_query_groups', [], [
			'domain' => 'posts',
			'is_active' => true,
			'purpose' => '*',

		] )*/

		$field_groups = \Toolset_Field_Group_Post_Factory::get_instance()
			->get_groups_by_post_type( $post_type_slug );

		// If $post_type_slug is the slug of an RFG, the field group will be loaded using an alternative method.
		// The RFG field group has "hidden" status, so this is why "get_groups_by_post_type" cannot load it.
		$maybe_rfg_field_group = \Toolset_Field_Group_Post_Factory::get_instance()->load_field_group( $post_type_slug );
		if (
			$maybe_rfg_field_group &&
			is_callable( array( $maybe_rfg_field_group, 'get_purpose' ) ) &&
			\Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP === $maybe_rfg_field_group->get_purpose()
		) {
			// ... the $post_type_slug is the slug of an RFG, so let's append this to the $fields_groups array.
 			$field_groups[] = $maybe_rfg_field_group;
		}

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

		$field_group_fields = array(
			'fields' => array(),
			'repeating_groups' => array(),
		);
		$factory = \Toolset_Field_Definition_Factory_Post::get_instance();
		$rfg_service = new \Types_Field_Group_Repeatable_Service();
		$slugs = $field_group->get_field_slugs();

		foreach ( $slugs as $slug ) {
			$field_definition = $factory->load_field_definition( $slug );
			$repeatable_group = $rfg_service->get_object_from_prefixed_string( $slug );
			if (
				null !== $field_definition &&
				$field_definition->is_managed_by_types()
			) {
				$field_group_fields['fields'][] = $field_definition;
			} elseif ( $repeatable_group ) {
				$repeatable_group_field_slugs = $repeatable_group->get_field_slugs();
				if ( ! empty( $repeatable_group_field_slugs ) ) {
					$field_group_fields['repeating_groups'][] = $repeatable_group;
				}
			}
		}

		$elligible_field_definitions = array_filter(
			$field_group_fields['fields'],
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

		$rfgs_as_field_models = array_values(
			array_map(
				function( \Types_Field_Group_Repeatable $rfg_definition ) {
					$field_type = 'rfg';
					return new FieldModel(
						$rfg_definition->get_slug(),
						$rfg_definition->get_name(),
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
			$field_group->get_name(),
			array_merge( $field_models, $rfgs_as_field_models ),
			\Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP === $field_group->get_purpose()
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
			default:
				return $text;
		}
	}

}
