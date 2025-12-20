<?php
/**
 * Render callback for the Terms Query Pagination Next block.
 *
 * @param array $attributes Block attributes.
 * @param string $content Block default content.
 * @param WP_Block $block Block instance.
 *
 * @return string Rendered block HTML.
 *
 * @package Terms_Query_Pagination
 */
function render_block_terms_query_pagination_next( $attributes, $content, $block ) {
	// Get term query from context.
	$term_query = isset( $block->context['termQuery'] ) ? $block->context['termQuery'] : array();

	if ( empty( $term_query ) ) {
		return '';
	}

	// Get current page.
	$current_page = Terms_Query_Pagination_Helper::get_current_page();

	// Check if next page exists.
	if ( ! Terms_Query_Pagination_Helper::has_next_page( $term_query, $current_page ) ) {
		return '';
	}

	// Get label from attributes or context.
	$label      = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Next Page', 'terms-query-pagination' );
	$show_label = isset( $block->context['showLabel'] ) ? $block->context['showLabel'] : true;
	$arrow_type = isset( $block->context['paginationArrow'] ) ? $block->context['paginationArrow'] : 'none';

	// Build label with arrow.
	$arrow_map = array(
		'none'    => '',
		'arrow'   => '→',
		'chevron' => '»',
	);

	$arrow = isset( $arrow_map[ $arrow_type ] ) ? $arrow_map[ $arrow_type ] : '';

	$display_label = $show_label ? $label : '';
	if ( $arrow && $show_label ) {
		$display_label = $display_label . ' ' . $arrow;
	} elseif ( $arrow ) {
		$display_label = $arrow;
	}

	// Generate next page URL.
	$next_page = $current_page + 1;
	$next_url  = Terms_Query_Pagination_Helper::get_page_url( $term_query, $next_page );

	// Build the link HTML.
	$wrapper_attributes = get_block_wrapper_attributes();
	$content            = sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( $next_url ),
		$wrapper_attributes,
		esc_html( $display_label )
	);

	// Add Interactivity API attributes if enhanced pagination is enabled.
	if ( ! empty( $block->context['enhancedPagination'] ) ) {
		$content = Terms_Query_Pagination_Helper::add_interactivity_attributes(
			$content,
			'terms-pagination-next-' . $next_page
		);
	}

	return $content;
}
