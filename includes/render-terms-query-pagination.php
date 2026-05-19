<?php
/**
 * Render callback for the Terms Query Pagination block.
 *
 * @param array $attributes Block attributes.
 * @param string $content Block default content.
 *
 * @return string Rendered block HTML.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function render_block_terms_query_pagination( $attributes, $content ) {
	if ( empty( trim( $content ) ) ) {
		return '';
	}

	$classes = ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) ? 'has-link-color' : '';

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'aria-label' => __( 'Terms Pagination', 'terms-query-pagination' ),
			'class'      => $classes,
		)
	);

	return sprintf(
		'<nav %1$s>%2$s</nav>',
		$wrapper_attributes,
		$content
	);
}
