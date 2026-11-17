<?php
/**
 * Main plugin class.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blocks.
 */
class Plugin {

	/**
	 * Hook the plugin into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register the slider and slide blocks from the build directory.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		foreach ( array( 'slider', 'slide' ) as $block ) {
			$dir = KADENZ_SLIDER_DIR . 'build/' . $block;
			if ( ! file_exists( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: %s: block build directory. */
							esc_html__( 'Kadenz Slider: the build directory "%s" is missing. Run "npm run build".', 'kadenz-slider' ),
							esc_html( 'build/' . $block )
						),
						esc_html( KADENZ_SLIDER_VERSION )
					);
				}
				continue;
			}
			register_block_type( $dir );
		}
	}
}
