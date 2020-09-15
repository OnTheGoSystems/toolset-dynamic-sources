<?php

namespace OTGS\Toolset\DynamicSources\Tests\Integrations;

use Toolset\DynamicSources\Integrations\WPML as SubjectWPML;

class WPML extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wpmlcore-6611
	 */
	public function it_should_initialize() {
		$subject = new SubjectWPML();

		\WP_Mock::expectFilterAdded(
			'wpml_found_strings_in_block',
			array( $subject, 'remove_dynamic_source_strings_from_block' ),
			10, 2
		);

		\WP_Mock::expectFilterAdded(
			'toolset/dynamic_sources/filters/shortcode_post_provider',
			array( $subject, 'convert_post_provider' )
		);

		$subject->initialize();
	}

	/**
	 * @test
	 * @group wpmlcore-6611
	 */
	public function it_should_remove_dynamic_source_shortcode_string_from_block() {
		$strings = array(
			(object) array(
				'value' => 'some value',
			),
			(object) array(
				'value' => '[' . \Toolset\DynamicSources\DynamicSources::SHORTCODE . ' foo="bar"]',
			),
		);

		$subject = new SubjectWPML();

		$filtered_strings = $subject->remove_dynamic_source_strings_from_block( $strings, $this->getBlock() );

		$this->assertCount( 1, $filtered_strings );
		$this->assertEquals( 'some value', $filtered_strings[0]->value );
	}

	/**
	 * @test
	 * @dataProvider dp_should_not_remove_string_containing_more_than_dynamic_source_shortcode
	 * @group toolsetblocks-628
	 *
	 * @param string $stringValue
	 */
	public function it_should_not_remove_string_containing_more_than_dynamic_source_shortcode( $stringValue ) {
		$strings = [
			(object) [
				'value' => $stringValue,
			],
		];

		$subject = new SubjectWPML();

		$filtered_strings = $subject->remove_dynamic_source_strings_from_block( $strings, $this->getBlock() );

		$this->assertCount( 1, $filtered_strings );
		$this->assertEquals( $stringValue, $filtered_strings[0]->value );
	}

	public function dp_should_not_remove_string_containing_more_than_dynamic_source_shortcode() {
		return [
			'text before' => [ 'some text [' . \Toolset\DynamicSources\DynamicSources::SHORTCODE . ' foo="bar"]' ],
			'text after'  => [ '[' . \Toolset\DynamicSources\DynamicSources::SHORTCODE . ' foo="bar"] som text' ],
		];
	}

	/**
	 * @test
	 * @group toolsetblocks-627
	 */
	public function it_should_remove_dynamic_source_strings_from_block_attributes() {
		$stringToRemove = 'The active dynamic attribute string to remove';
		$stringToKeep   = 'String for the inactive dynamic attribute (to keep)';

		$strings = [
			(object) [
				'value' => $stringToRemove,
			],
			(object) [
				'value' => $stringToKeep,
			],
		];

		$attributes = [
			'dynamicAttributeActive'    => $stringToRemove,
			'dynamicAttributeInactive'  => $stringToKeep,
			'dynamicAttributeNotString' => [ 'something' ], // Not sure this case exist but I prefer to test it
			'dynamic' => [
				'dynamicAttributeActive'                    => [ 'isActive' => true ],
				'dynamicAttributeInactive'                  => [],
				'dynamicAttributeNotString'                 => [ 'isActive' => true ],
				'dynamicAttributeThatDoesNotActuallyExists' => [ 'isActive' => true ],
			],
		];

		$block = $this->getBlock( $attributes );

		$subject = new SubjectWPML();

		$filtered_strings = $subject->remove_dynamic_source_strings_from_block( $strings, $block );

		$this->assertCount( 1, $filtered_strings );
		$this->assertEquals( $stringToKeep, $filtered_strings[0]->value );
	}

	/**
	 * @test
	 * @group toolsetblocks-399
	 */
	public function it_should_not_convert_post_provider_if_not_other_post() {
		$subject = new SubjectWPML();

		$post_provider = '_current_post';

		$this->assertEquals( $post_provider, $subject->convert_post_provider( $post_provider ) );
	}

	/**
	 * @test
	 * @group toolsetblocks-399
	 */
	public function it_should_convert_post_provider_if_other_post() {
		$post_id                 = 65;
		$converted_id            = 99;
		$post_type               = 'page';
		$post_provider           = 'custom_post_type|' . $post_type . '|' . $post_id;
		$converted_post_provider = 'custom_post_type|' . $post_type . '|' . $converted_id;

		\WP_Mock::onFilter( 'wpml_object_id' )
			->with( $post_id, $post_type )
			->reply( $converted_id );

		$subject = new SubjectWPML();

		$this->assertEquals( $converted_post_provider, $subject->convert_post_provider( $post_provider ) );
	}

	/**
	 * @param array $attrs
	 *
	 * @return \WP_Block_Parser_Block|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getBlock( $attrs = [] ) {
		$block = $this->getMockBuilder( '\WP_Block_Parser_Block' )->getMock();
		$block->attrs = $attrs;

		return $block;
	}
}
