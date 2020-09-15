<?php

namespace OTGS\Toolset\DynamicSources\Tests\Sources;

use Toolset\DynamicSources\Sources\MediaFeaturedImageData as SubjectMediaFeaturedImageData;
use Toolset\DynamicSources\DynamicSources;

/**
 * Test the Media Featured Image Data source class.
 *
 * @group DynamicSources
 * @group Sources
 *
 * @since 3.0.1
 */
class MediaFeaturedImageData extends \OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_gets_the_title() {
		$subject = new SubjectMediaFeaturedImageData();
		$this->assertEquals( 'Featured Image Data', $subject->get_title() );
	}

	/**
	 * @test
	 */
	public function it_gets_group() {
		$subject = new SubjectMediaFeaturedImageData();
		$this->assertEquals( DynamicSources::MEDIA_GROUP, $subject->get_group() );
	}

	/**
	 * @test
	 */
	public function it_gets_categories() {
		$subject = new SubjectMediaFeaturedImageData();
		$expected_categories = array( DynamicSources::TEXT_CATEGORY, DynamicSources::URL_CATEGORY, DynamicSources::IMAGE_CATEGORY );
		$this->assertSame( $expected_categories, $subject->get_categories() );
	}

	public function content_data_provider() {
		return array(
			// Note: Array keys are just for convenience.
			// Order of the elements in individual data sets is what actually matters.
			'data_1' => array(
				'expected_attachment' => (object) array(
					'ID' => null,
				),
				'expected_content' => '',
			),
			'data_2' => array(
				'expected_attachment' => (object) array(
					'ID' => 0,
				),
				'expected_content' => '',
			),
			'data_3' => array(
				'expected_attachment' => (object) array(
					'ID' => 1,
				),
				'expected_content' => '',
				'expected_image_source' => false,
			),
			'data_4' => array(
				'expected_attachment' => (object) array(
					'ID' => 1,
				),
				'expected_content' => '',
				'expected_image_source' => null,
			),
			'data_5' => array(
				'expected_attachment' => (object) array(
					'ID' => 1,
				),
				'expected_content' => 'lorem',
				'expected_image_source' => array( 'lorem', 'ipsum', 'dolor' ),
			),
			'data_6' => array(
				'expected_attachment' => (object) array(
					'ID' => 1,
				),
				'expected_content' => 'lorem',
				'expected_image_source' => array( 'lorem', 'ipsum', 'dolor' ),
				'field' => null,
				'attribute' => array( 'size' => 'ipsum' ),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider content_data_provider
	 *
	 * @param \stdClass  $expected_attachment
	 * @param string     $expected_content
	 * @param null|array $expected_image_source
	 * @param null       $field
	 * @param null|array $attributes
	 */
	public function it_gets_content( $expected_attachment, $expected_content, $expected_image_source = null, $field = null, $attributes = null ) {
		\WP_Mock::userFunction( 'get_post_thumbnail_id' )->andReturn( $expected_attachment->ID );
		\WP_Mock::userFunction( 'get_post' )->with( 0 )->andReturnNull();
		\WP_Mock::userFunction( 'get_post' )->with( integerValue() )->andReturn( $expected_attachment );
		$size = isset( $attributes['size'] ) ? $attributes['size'] : 'full';
		\WP_Mock::userFunction( 'wp_get_attachment_image_src' )->with( $expected_attachment->ID, $size )->andReturn( $expected_image_source );

		$subject = new SubjectMediaFeaturedImageData();
		$this->assertEquals( $expected_content, $subject->get_content( $field, $attributes ) );
	}
}
