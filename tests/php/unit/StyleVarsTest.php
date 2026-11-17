<?php
/**
 * Unit tests for Style_Vars.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kadenz\Slider\Attributes;
use Kadenz\Slider\Style_Vars;
use PHPUnit\Framework\TestCase;

/**
 * Tests the inline style generation.
 */
class StyleVarsTest extends TestCase {

	/**
	 * Set up Brain Monkey and stub sanitize_text_field().
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return trim( strip_tags( $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
			}
		);
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
	 * Data provider: raw attributes and expected style string.
	 *
	 * @return array
	 */
	public function provide_cases() {
		return array(
			'defaults give empty string'   => array( array(), '' ),
			'per view md and lg only'      => array(
				array(
					'perView' => array(
						'base' => 1,
						'md'   => 2,
						'lg'   => 3,
					),
				),
				'--kadenz-per-view-md:2;--kadenz-per-view-lg:3;',
			),
			'per view all three'           => array(
				array(
					'perView' => array(
						'base' => 2,
						'md'   => 3,
						'lg'   => 4,
					),
				),
				'--kadenz-per-view-base:2;--kadenz-per-view-md:3;--kadenz-per-view-lg:4;',
			),
			'fade suppresses per view'     => array(
				array(
					'effect'  => 'fade',
					'perView' => array( 'base' => 3 ),
				),
				'',
			),
			'gap'                          => array( array( 'gap' => '2rem' ), '--kadenz-gap:2rem;' ),
			'gap preset'                   => array( array( 'gap' => 'var:preset|spacing|40' ), '--kadenz-gap:var(--wp--preset--spacing--40);' ),
			'gap default not emitted'      => array( array( 'gap' => '1.5rem' ), '' ),
			'peek'                         => array( array( 'peek' => '10px' ), '--kadenz-peek:10px;' ),
			'aspect ratio'                 => array( array( 'aspectRatio' => '4/3' ), '--kadenz-aspect-ratio:4/3;' ),
			'aspect ratio with spaces'     => array( array( 'aspectRatio' => '4 / 3' ), '--kadenz-aspect-ratio:4 / 3;' ),
			'aspect ratio default spaced'  => array( array( 'aspectRatio' => '16 / 9' ), '' ),
			'auto height has no aspect'    => array(
				array(
					'heightMode'  => 'auto',
					'aspectRatio' => '4/3',
				),
				'',
			),
			'min height has no aspect'     => array(
				array(
					'heightMode'  => 'min',
					'aspectRatio' => '4/3',
				),
				'',
			),
			'min height'                   => array(
				array(
					'heightMode' => 'min',
					'minHeight'  => '300px',
				),
				'--kadenz-min-height:300px;',
			),
			'min height empty'             => array( array( 'heightMode' => 'min' ), '' ),
			'min height ignored in aspect' => array( array( 'minHeight' => '300px' ), '' ),
			'interval with autoplay'       => array(
				array(
					'autoplay' => true,
					'interval' => 3000,
				),
				'--kadenz-interval:3000ms;',
			),
			'interval default autoplay'    => array( array( 'autoplay' => true ), '' ),
			'interval without autoplay'    => array( array( 'interval' => 3000 ), '' ),
			'fixed order'                  => array(
				array(
					'autoplay'    => true,
					'interval'    => 2500,
					'heightMode'  => 'aspect',
					'aspectRatio' => '1/1',
					'peek'        => '1rem',
					'gap'         => '0',
					'perView'     => array( 'md' => 2 ),
				),
				'--kadenz-per-view-md:2;--kadenz-per-view-lg:2;--kadenz-gap:0;--kadenz-peek:1rem;--kadenz-aspect-ratio:1/1;--kadenz-interval:2500ms;',
			),
		);
	}

	/**
	 * Tests the generated style for normalized input.
	 *
	 * @dataProvider provide_cases
	 *
	 * @param array  $raw      Raw attributes.
	 * @param string $expected Expected style string.
	 * @return void
	 */
	public function test_from_attributes( array $raw, $expected ) {
		$this->assertSame( $expected, Style_Vars::from_attributes( Attributes::normalize( $raw ) ) );
	}

	/**
	 * Malicious values that bypass normalization are dropped by the final check.
	 *
	 * @return void
	 */
	public function test_final_check_drops_unsafe_values() {
		$style = Style_Vars::from_attributes(
			array(
				'gap'         => '1rem;color:red',
				'peek'        => 'url(x)',
				'heightMode'  => 'aspect',
				'aspectRatio' => '4/3"',
				'perView'     => array(
					'base' => 1,
					'md'   => 2,
					'lg'   => 1,
				),
			)
		);
		$this->assertSame( '--kadenz-per-view-md:2;', $style );
	}

	/**
	 * Incomplete input does not raise notices.
	 *
	 * @return void
	 */
	public function test_missing_keys_are_ignored() {
		$this->assertSame( '', Style_Vars::from_attributes( array() ) );
	}
}
