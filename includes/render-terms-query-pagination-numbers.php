<?php
/**
 * Render callback for the Terms Query Pagination Numbers block.
 *
 * @param array $attributes Block attributes.
 * @param string $content Block default content.
 * @param WP_Block $block Block instance.
 *
 * @return string Rendered block HTML.
 * @package Terms_Query_Pagination
 *
 */
function render_block_terms_query_pagination_numbers( $attributes, $content, $block ) {
	// Get term query from context.
	$term_query = isset( $block->context['termQuery'] ) ? $block->context['termQuery'] : array();

	if ( empty( $term_query ) ) {
		return '';
	}

	// Get current page.
	$current_page = Terms_Query_Pagination_Helper::get_current_page();

	// Get total pages.
	$total_pages = Terms_Query_Pagination_Helper::get_total_pages( $term_query );

	if ( $total_pages <= 1 ) {
		return '';
	}

	// Get mid_size from attributes.
	$mid_size = isset( $attributes['midSize'] ) ? absint( $attributes['midSize'] ) : 2;

	// Build base URL for pagination links.
	// Use a simple query-arg based pagination model with "termspage".
	$base_url = add_query_arg( 'termspage', '%#%' );

	// Generate pagination links.
	$pagination_links = paginate_links(
		array(
			'base'      => $base_url,
			'format'    => '',
			'current'   => $current_page,
			'total'     => $total_pages,
			'mid_size'  => $mid_size,
			'end_size'  => 1,
			'prev_next' => false,
			'type'      => 'plain',
		)
	);

	if ( empty( $pagination_links ) ) {
		return '';
	}

	// Add Interactivity API attributes if enhanced pagination is enabled.
	if ( ! empty( $block->context['enhancedPagination'] ) && class_exists( 'WP_HTML_Tag_Processor' ) ) {
		$processor = new WP_HTML_Tag_Processor( $pagination_links );
		$page_key  = 0;

		while ( $processor->next_tag( 'a' ) ) {
			$processor->set_attribute( 'data-wp-key', 'terms-pagination-numbers-' . $page_key );
			$processor->set_attribute( 'data-wp-on--click', 'actions.navigate' );
			$processor->set_attribute( 'data-wp-on--mouseenter', 'actions.prefetch' );
			++ $page_key;
		}

		$pagination_links = $processor->get_updated_html();
	}

	// Wrap in block wrapper.
	$wrapper_attributes = get_block_wrapper_attributes();

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$pagination_links
	);
}
