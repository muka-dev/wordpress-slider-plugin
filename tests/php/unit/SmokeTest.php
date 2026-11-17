<?php
/**
 * Smoke test for the unit suite.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Checks that the unit environment works.
 */
class SmokeTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * The main plugin class is loadable.
	 *
	 * @return void
	 */
	public function test_plugin_class_exists() {
		$this->assertTrue( class_exists( 'Kadenz\Slider\Plugin' ) );
	}
}
