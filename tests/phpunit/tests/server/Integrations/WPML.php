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
}
