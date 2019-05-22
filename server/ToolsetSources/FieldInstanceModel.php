<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Represents a single field instance.
 */
class FieldInstanceModel {


	/** @var FieldModel */
	private $field;

	/** @var array Attributes from the shortcode */
	private $attributes = array();

	/**
	 * FieldInstanceModel constructor.
	 *
	 * @param FieldModel $field
	 * @param array $attributes
	 */
	public function __construct( FieldModel $field, $attributes = null ) {
		$this->field = $field;

		if( is_array( $attributes ) ) {
			$this->attributes = $attributes;
		}
	}


	/**
	 * @return string
	 */
	public function get_field_value() {
		$args = [];
		switch ( $this->field->get_type() ) {
			case 'google_address':
				$args = [ 'format' => 'FIELD_ADDRESS' ];
				break;
			case 'email':
			case 'embed':
			case 'url':
			case 'video':
			case 'audio':
			case 'date':
				$args = [ 'output' => 'raw' ];
				break;
			case 'image':
				$args = isset( $this->attributes['size'] )
					? [
						'url' => true,
						'size' => $this->attributes['size']
					] : [
						'output' => 'raw'
					];
				break;
			case 'checkboxes':
				$args = [
					'output' => 'raw',
					'separator' => ', '
				];
				break;
		}

		if ( $this->field->is_repetitive() ) {
			$args[ 'separator' ] = '|#|'; // There is not a Types function for getting the field value as an array
			$value = types_render_field( $this->field->get_slug(), $args );
			$value = explode( $args[ 'separator' ], $value );
		} else {
			$value = types_render_field( $this->field->get_slug(), $args );
		}

		return $value;
	}

}
