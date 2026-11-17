<?php
/**
 * Block registration integration test.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Integration;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * Checks that both blocks are registered with the expected relationship.
 */
class BlockRegistrationTest extends WP_UnitTestCase {

	/**
	 * Both blocks are registered.
	 *
	 * @return void
	 */
	public function test_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'kadenz/slider' ) );
		$this->assertTrue( $registry->is_registered( 'kadenz/slide' ) );
	}

	/**
	 * The slide block can only be placed inside the slider block.
	 *
	 * @return void
	 */
	public function test_slide_has_slider_as_parent() {
		$slide = WP_Block_Type_Registry::get_instance()->get_registered( 'kadenz/slide' );
		$this->assertNotNull( $slide );
		$this->assertSame( array( 'kadenz/slider' ), $slide->parent );
	}
}
