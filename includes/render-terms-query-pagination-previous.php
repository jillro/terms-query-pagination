<?php
/**
 * Render callback for the Terms Query Pagination Previous block.
 *
 * @package Terms_Query_Pagination
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block HTML.
 */
function render_block_terms_query_pagination_previous( $attributes, $content, $block ) {
	// Get term query from context.
	$term_query = isset( $block->context['termQuery'] ) ? $block->context['termQuery'] : array();

	if ( empty( $term_query ) ) {
		return '';
	}

	// Get current page.
	$current_page = Terms_Query_Pagination_Helper::get_current_page();

	// Check if previous page exists.
	if ( ! Terms_Query_Pagination_Helper::has_previous_page( $current_page ) ) {
		return '';
	}

	// Get label from attributes or context.
	$label        = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Previous Page', 'terms-query-pagination' );
	$show_label   = isset( $block->context['showLabel'] ) ? $block->context['showLabel'] : true;
	$arrow_type   = isset( $block->context['paginationArrow'] ) ? $block->context['paginationArrow'] : 'none';

	// Build label with arrow.
	$arrow_map = array(
		'none'    => '',
		'arrow'   => '←',
		'chevron' => '«',
	);

	$arrow = isset( $arrow_map[ $arrow_type ] ) ? $arrow_map[ $arrow_type ] : '';

	$display_label = $show_label ? $label : '';
	if ( $arrow && $show_label ) {
		$display_label = $arrow . ' ' . $display_label;
	} elseif ( $arrow ) {
		$display_label = $arrow;
	}

	// Generate previous page URL.
	$previous_page = $current_page - 1;
	$previous_url  = Terms_Query_Pagination_Helper::get_page_url( $previous_page );

	// Build the link HTML.
	$wrapper_attributes = get_block_wrapper_attributes();
	$content            = sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( $previous_url ),
		$wrapper_attributes,
		esc_html( $display_label )
	);

	// Add Interactivity API attributes if enhanced pagination is enabled.
	if ( ! empty( $block->context['enhancedPagination'] ) ) {
		$content = Terms_Query_Pagination_Helper::add_interactivity_attributes(
			$content,
			'terms-pagination-previous-' . $previous_page
		);
	}

	return $content;
}
