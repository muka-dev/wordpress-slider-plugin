<?php
/**
 * Slide block render callback.
 *
 * @package Kadenz\Slider
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Wrapper attributes are escaped by core; content is rendered block markup.
echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'kadenz-slide' ) ) . '>' . $content . '</div>';
