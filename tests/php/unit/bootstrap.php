<?php
/**
 * Bootstrap for the unit suite. WordPress is NOT loaded here.
 *
 * @package Kadenz\Slider
 */

$kadenz_slider_root = dirname( __DIR__, 3 );

require_once $kadenz_slider_root . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $kadenz_slider_root . '/' );
}
if ( ! defined( 'KADENZ_SLIDER_VERSION' ) ) {
	define( 'KADENZ_SLIDER_VERSION', '0.1.0' );
}
if ( ! defined( 'KADENZ_SLIDER_DIR' ) ) {
	define( 'KADENZ_SLIDER_DIR', $kadenz_slider_root . '/' );
}

foreach ( glob( $kadenz_slider_root . '/includes/class-*.php' ) as $kadenz_slider_file ) {
	require_once $kadenz_slider_file;
}
