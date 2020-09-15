<?php

namespace Toolset\DynamicSources\ToolsetSources;


use Toolset\DynamicSources\PostProvider;
use Toolset\DynamicSources\Sources\AbstractSource;

/**
 * Dynamic data source representing a Custom Field Group from Toolset.
 */
class CustomFieldGroupSource
	extends AbstractSource
	implements PotentiallyEmptySource
{

	const SOURCE_NAME_PREFIX = 'toolset_custom_field';

	const SOURCE_NAME_SEPARATOR = '|';


	/** @var FieldGroupModel */
	private $field_group;


	/** @var CustomFieldService */
	private $custom_field_service;


	/**
	 * CustomFieldGroupSource constructor.
	 *
	 * @param FieldGroupModel $field_group
	 * @param CustomFieldService $custom_field_service
	 */
	public function __construct( FieldGroupModel $field_group, CustomFieldService $custom_field_service ) {
		$this->field_group = $field_group;
		$this->custom_field_service = $custom_field_service;
	}


	/**
	 * @return bool
	 */
	public function has_content() {
		return count( $this->get_fields() ) > 0;
	}


	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->field_group->get_display_name();
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_group() {
		return Main::SOURCE_GROUP_KEY;
	}


	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'text' ];
	}


	/**
	 * Get the content of the selected custom field for the current post.
	 *
	 * Returns an empty string if not applicable for any reason.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return string
	 */
	public function get_content( $field = null, $attributes = null ) {
		$selected_field = $this->field_group->get_field_definition( $field );
		if( null === $selected_field ) {
			return '';
		}

		$field_instance = $this->custom_field_service->get_field_instance_for_current_post( $selected_field, $attributes );
		if( null === $field_instance ) {
			return '';
		}

		return $field_instance->get_field_value();
	}


	/**
	 * This source will be usable only if any of the post types offered by the post provider
	 * have the field group of this source assigned.
	 *
	 * @param PostProvider $post_provider
	 *
	 * @return bool
	 */
	public function is_usable_with_post_provider( PostProvider $post_provider ) {

		foreach( $post_provider->get_post_types() as $post_type ) {
			if(
				in_array(
					$this->field_group->get_slug(),
					$this->custom_field_service->get_group_slugs_by_type( $post_type )
				)
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Name uniquely identifying this custom source.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::SOURCE_NAME_PREFIX . self::SOURCE_NAME_SEPARATOR . $this->field_group->get_slug();
	}


	/**
	 * Builds the definition of dynamic content source fields, as required by the Toolset Blocks API.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array_values( array_map(
			function( FieldModel $field ) {
				return [
					'label' => $field->get_name(),
					'value' => $field->get_slug(),
					'categories' => $field->get_categories(),
					'type' => $field->get_type_slug(),
					'fieldOptions' => $field->get_options(),
					'is_repetitive' => $field->is_repeatable(),
				];
			},
			$this->field_group->get_field_definitions()
		) );
	}
}
