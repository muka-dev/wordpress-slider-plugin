<?php
/**
 * Plugin Name:       Kadenz Slider
 * Description:       An ad-free, accessible, block-native slider for the WordPress block editor.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Kadenz
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kadenz-slider
 *
 * @package Kadenz\Slider
 */

defined( 'ABSPATH' ) || exit;

define( 'KADENZ_SLIDER_VERSION', '0.1.0' );
define( 'KADENZ_SLIDER_FILE', __FILE__ );
define( 'KADENZ_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'KADENZ_SLIDER_URL', plugin_dir_url( __FILE__ ) );

require_once KADENZ_SLIDER_DIR . 'includes/class-attributes.php';
require_once KADENZ_SLIDER_DIR . 'includes/class-style-vars.php';
require_once KADENZ_SLIDER_DIR . 'includes/class-slide-markup.php';
require_once KADENZ_SLIDER_DIR . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'Kadenz\Slider\Plugin', 'init' ) );
