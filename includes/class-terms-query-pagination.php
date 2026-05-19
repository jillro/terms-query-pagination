<?php
/**
 * Terms Query Pagination Helper Class
 *
 * @package Terms_Query_Pagination
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Terms_Query_Pagination_Helper
 *
 * Provides utility methods for term pagination logic.
 */
class Terms_Query_Pagination_Helper {

	/**
	 * Get the current page number from URL parameters.
	 *
	 * @return int Current page number (minimum 1).
	 */
	public static function get_current_page() {
		global $wp_query;
		$page = isset( $wp_query->query_vars['termspage'] ) ? absint( $wp_query->query_vars['termspage'] ) : 1;

		return max( 1, $page );
	}

	/**
	 * Calculate total number of pages based on term query.
	 *
	 * @param array $term_query The term query configuration from block context.
	 *
	 * @return int Total number of pages.
	 */
	public static function get_total_pages( $term_query ) {
		if ( empty( $term_query ) ) {
			return 1;
		}

		$per_page = (int) ( $term_query['perPage'] ?? 10 );
		if ( $per_page <= 0 ) {
			return 1;
		}

		// Build count arguments matching the query filters.
		$count_args = self::build_count_args( $term_query );

		// Get total term count.
		$total_terms = wp_count_terms( $count_args );

		// Handle WP_Error.
		if ( is_wp_error( $total_terms ) ) {
			return 1;
		}

		return max( 1, (int) ceil( $total_terms / $per_page ) );
	}


	/**
	 * Generate URL for a specific page number.
	 *
	 * @param int $page_number The page number to link to.
	 *
	 * @return string The URL with page parameter.
	 */
	public static function get_page_url( $term_query, $page_number ) {
		global $wp_rewrite;
		$taxonomy = $term_query['taxonomy'] ?? 'category';

		$page_number = absint( $page_number );

		if ( $wp_rewrite->using_permalinks() ) {
			if ( $page_number <= 1 ) {
				// Remove termspage parameter for page 1.
				return get_permalink();
			}

			return user_trailingslashit( trailingslashit( get_permalink() ) . $taxonomy . '-page/' . $page_number );
		} else {
			if ( $page_number <= 1 ) {
				// Remove termspage parameter for page 1.
				return remove_query_arg( 'termspage' );
			}

			return add_query_arg( 'termspage', $page_number );
		}
	}

	/**
	 * Build arguments for wp_count_terms() based on term query context.
	 *
	 * @param array $term_query The term query configuration from block context.
	 *
	 * @return array Arguments for wp_count_terms().
	 */
	public static function build_count_args( $term_query ) {
		// Taxonomy can be provided as string or array (e.g., 'taxonomy' or 'taxonomies').
		$taxonomy   = $term_query['taxonomy'] ?? 'category';
		$hide_empty = (bool) ( $term_query['hideEmpty'] ?? true );

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
		);

		// Add include filter if specified.
		if ( ! empty( $term_query['include'] ) && is_array( $term_query['include'] ) ) {
			$args['include'] = array_map( 'absint', $term_query['include'] );
		}

		return $args;
	}


	/**
	 * Check if a next page exists.
	 *
	 * @param array $term_query The term query configuration.
	 * @param int $current_page The current page number.
	 *
	 * @return bool True if next page exists, false otherwise.
	 */
	public static function has_next_page( $term_query, $current_page ) {
		$total_pages = self::get_total_pages( $term_query );

		return $current_page < $total_pages;
	}

	/**
	 * Check if a previous page exists.
	 *
	 * @param int $current_page The current page number.
	 *
	 * @return bool True if previous page exists, false otherwise.
	 */
	public static function has_previous_page( $current_page ) {
		return $current_page > 1;
	}

	/**
	 * Add Interactivity API attributes to HTML content.
	 *
	 * @param string $content The HTML content to process.
	 * @param string $key Unique key for the element.
	 *
	 * @return string Modified HTML with Interactivity API attributes.
	 */
	public static function add_interactivity_attributes( $content, $key ) {
		if ( empty( $content ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );
		if ( $processor->next_tag( 'a' ) ) {
			$processor->set_attribute( 'data-wp-key', $key );
			$processor->set_attribute( 'data-wp-on--click', 'actions.navigate' );
			$processor->set_attribute( 'data-wp-on--mouseenter', 'actions.prefetch' );
			$content = $processor->get_updated_html();
		}

		return $content;
	}
}
