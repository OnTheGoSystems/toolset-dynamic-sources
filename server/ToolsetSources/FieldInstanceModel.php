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
	 * Adjust the timestamp by removing the offset provided by the timezone setting of the site.
	 *
	 * @param int $timestamp
	 * @return int
	 */
	private function unoffset_timestamp( $timestamp ) {
		$current_gmt_timestamp = current_time( 'timestamp', true );
		$current_local_timestamp = current_time( 'timestamp', false );

		$offset = $current_local_timestamp - $current_gmt_timestamp;

		return $timestamp - $offset;
	}

	/**
	 * If given a date field, unoffset it's timestamp, otherwise do nothing.
	 *
	 * @param mixed $field
	 *
	 * @return mixed
	 */
	private function maybe_unoffset_timestamp( $field ) {
		if ( 'date' === $this->field->get_type_slug() ) {
			return $this->unoffset_timestamp( (int) $field );
		}
		return $field;
	}

	/**
	 * @return string
	 */
	public function get_field_value() {
		$args = [];
		switch ( $this->field->get_type_slug() ) {
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

		if ( $this->field->is_repeatable() ) {
			$args[ 'separator' ] = '|#|'; // There is not a Types function for getting the field value as an array
			$value = types_render_field( $this->field->get_slug(), $args );
			$value = explode( $args[ 'separator' ], $value );
		} else {
			$value = $this->maybe_unoffset_timestamp( types_render_field( $this->field->get_slug(), $args ) );
		}

		return $value;
	}

}
