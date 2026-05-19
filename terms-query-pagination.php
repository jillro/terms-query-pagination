<?php
/**
 * Plugin Name: Terms Query Pagination Block
 * Plugin URI: https://lqmstudio.com/terms-query-pagination-wordpress
 * Description: Adds pagination blocks for the WordPress 6.9 Terms Query block, following the pattern of WordPress Core pagination blocks.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: LQM Studio
 * Author URI: https://lqmstudio.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: terms-query-pagination
 *
 * @package Terms_Query_Pagination
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'TERMS_QUERY_PAGINATION_VERSION', '1.0.0' );
define( 'TERMS_QUERY_PAGINATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TERMS_QUERY_PAGINATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function terms_query_pagination_init() {
	// Load helper class.
	require_once TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'includes/class-terms-query-pagination.php';

	// Load render callbacks.
	require_once TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'includes/render-terms-query-pagination.php';
	require_once TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'includes/render-terms-query-pagination-next.php';
	require_once TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'includes/render-terms-query-pagination-previous.php';
	require_once TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'includes/render-terms-query-pagination-numbers.php';

	register_block_type(
		TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'build/blocks/terms-query-pagination',
		array(
			'render_callback' => 'render_block_terms_query_pagination',
			'category'        => 'theme'
		)
	);

	register_block_type(
		TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'build/blocks/terms-query-pagination-next',
		array(
			'render_callback' => 'render_block_terms_query_pagination_next',
		)
	);

	register_block_type(
		TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'build/blocks/terms-query-pagination-previous',
		array(
			'render_callback' => 'render_block_terms_query_pagination_previous',
		)
	);

	register_block_type(
		TERMS_QUERY_PAGINATION_PLUGIN_DIR . 'build/blocks/terms-query-pagination-numbers',
		array(
			'render_callback' => 'render_block_terms_query_pagination_numbers',
		)
	);
}

add_action( 'init', 'terms_query_pagination_init' );

/**
 * Internal state to know when we're rendering a core/term-template block.
 * We use a depth counter to support nested renders safely.
 */
function &terms_query_pagination_get_context_depth_ref() {
	static $depth = 0;

	return $depth;
}

/**
 * Before rendering any block, if it's core/term-template, enable scoped filtering.
 *
 * @param string|null $pre_render Short-circuit value (unused here).
 * @param array $parsed_block Parsed block array.
 *
 * @return string|null Unchanged.
 */
function terms_query_pagination_enter_term_template_block( $pre_render, $parsed_block, $parent_block ) {
	$block_name = isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';
	$depth      =& terms_query_pagination_get_context_depth_ref();

	// If we're entering core/term-template block or any nested-block, increase the depth counter.
	if ( $depth > 0 || 'core/term-template' == $block_name ) {
		$depth ++;
	}

	return $pre_render;
}

add_filter( 'pre_render_block', 'terms_query_pagination_enter_term_template_block', 10, 3 );


function terms_query_pagination_exit_term_template_block( $block_content, $block, $instance ) {
	$depth =& terms_query_pagination_get_context_depth_ref();

	// If we're exiting core/term-template block or any dynamic nested-block, decrease the depth counter.
	if ( $depth > 0 && $instance->name !== 'core/null' ) {
		$depth --;
	}


	return $block_content;
}

add_filter( 'render_block', 'terms_query_pagination_exit_term_template_block', 10, 3 );

/**
 * Inject offset into WP_Term_Query args through `get_terms_args` while rendering the term template block.
 *
 * WordPress does not paginate terms natively, so we emulate pagination with an `offset`
 * calculated from the `termspage` URL parameter. This filter is only attached while
 * rendering `core/term-template`, to avoid affecting unrelated queries.
 *
 * @param array $query WP_Term_Query.
 *
 * @return void.
 */
function terms_query_pagination_parse_term_query( $query ) {
	// Front end only.
	if ( is_admin() ) {
		return;
	}

	$depth =& terms_query_pagination_get_context_depth_ref();
	if ( $depth != 1 ) {
		return;
	}

	remove_action( 'parse_term_query', 'terms_query_pagination_parse_term_query', 10 );

	// We need a per-page value coming from the Terms Query block, passed as `number`.
	$per_page = isset( $query->query_vars['number'] ) ? absint( $query->query_vars['number'] ) : 0;
	if ( $per_page <= 0 ) {
		return;
	}

	if ( ! class_exists( 'Terms_Query_Pagination_Helper' ) ) {
		return;
	}

	$current_page = Terms_Query_Pagination_Helper::get_current_page();
	if ( $current_page <= 1 ) {
		return;
	}

	$computed_offset             = ( $current_page - 1 ) * $per_page;
	$query->query_vars['offset'] = $computed_offset;
}

add_action( 'parse_term_query', 'terms_query_pagination_parse_term_query', 10, 1 );

/**
 * Register a `{taxonomy}-page` rewrite endpoint for every taxonomy so pretty
 * permalinks like `/{tax}-page/2` resolve to the `termspage` query var.
 */
function terms_query_pagination_add_taxonomy_page_rewrite() {
	$taxonomies = get_taxonomies();
	foreach ( $taxonomies as $tax ) {
		add_rewrite_endpoint(
			$tax . '-page',
			EP_ALL,
			'termspage'
		);
	}

}

add_action( 'init', 'terms_query_pagination_add_taxonomy_page_rewrite' );

/**
 * On activation, register the rewrite endpoint and flush rewrite rules so
 * pretty pagination permalinks work immediately without re-saving permalinks.
 */
function terms_query_pagination_activate() {
	terms_query_pagination_add_taxonomy_page_rewrite();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'terms_query_pagination_activate' );

/**
 * On deactivation, flush rewrite rules to drop the plugin's endpoint.
 */
function terms_query_pagination_deactivate() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'terms_query_pagination_deactivate' );
