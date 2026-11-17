<?php
/**
 * Bootstrap for the integration suite (loads WordPress via wp-phpunit).
 *
 * @package Kadenz\Slider
 */

$kadenz_slider_root = dirname( __DIR__, 3 );

require_once $kadenz_slider_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// wp-env provides WP_TESTS_DIR; otherwise fall back to the wp-phpunit package.
$kadenz_slider_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $kadenz_slider_tests_dir ) {
	$kadenz_slider_tests_dir = $kadenz_slider_root . '/vendor/wp-phpunit/wp-phpunit';
}

require_once $kadenz_slider_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $kadenz_slider_root ) {
		require $kadenz_slider_root . '/kadenz-slider.php';
	}
);

require $kadenz_slider_tests_dir . '/includes/bootstrap.php';
