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
			array( $subject, 'remove_dynamic_source_strings_from_block' )
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
	public function it_should_remove_dynamic_source_strings_from_block() {
		$strings = array(
			(object) array(
				'value' => 'some value',
			),
			(object) array(
				'value' => '[' . \Toolset\DynamicSources\DynamicSources::SHORTCODE . ' foo="bar"]',
			),
		);

		$subject = new SubjectWPML();

		$filtered_strings = $subject->remove_dynamic_source_strings_from_block( $strings );

		$this->assertCount( 1, $filtered_strings );
		$this->assertEquals( 'some value', $filtered_strings[0]->value );
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
}
