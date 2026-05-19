<?php
/**
 * Idempotent fixture seeding for the Playwright e2e suite.
 *
 * Run inside the wp-env cli container via global-setup.js:
 *   wp eval-file wp-content/plugins/terms-query-pagination/tests/e2e/seed.php
 *
 * Creates the two scenarios the specs assert against:
 *  - hierarchical:     23 "Category NN" + default Uncategorized (24) -> page /tqp-test/
 *  - non-hierarchical:  15 "Tag NN"                                   -> page /tqp-test-tags/
 *
 * @package Terms_Query_Pagination
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the terms-query + pagination block markup for a taxonomy.
 *
 * @param string $taxonomy Taxonomy slug.
 * @return string Serialized block content.
 */
function tqp_seed_block_content( $taxonomy ) {
	$term_query = wp_json_encode(
		array(
			'termQuery' => array(
				'perPage'    => 6,
				'taxonomy'   => $taxonomy,
				'order'      => 'asc',
				'orderBy'    => 'name',
				'include'    => array(),
				'hideEmpty'  => false,
				'showNested' => false,
				'inherit'    => false,
			),
		)
	);

	return <<<HTML
<!-- wp:terms-query $term_query -->
<div class="wp-block-terms-query">
<!-- wp:term-template -->
<!-- wp:term-name {"isLink":true} /-->
<!-- /wp:term-template -->
<!-- wp:terms-query-pagination/terms-query-pagination -->
<!-- wp:terms-query-pagination/terms-query-pagination-previous /-->
<!-- wp:terms-query-pagination/terms-query-pagination-numbers /-->
<!-- wp:terms-query-pagination/terms-query-pagination-next /-->
<!-- /wp:terms-query-pagination/terms-query-pagination -->
</div>
<!-- /wp:terms-query -->
HTML;
}

/**
 * Create a published page at a given slug, replacing any existing one.
 *
 * @param string $slug     Page slug.
 * @param string $title    Page title.
 * @param string $taxonomy Taxonomy slug for the block.
 * @return int Page ID.
 */
function tqp_seed_page( $slug, $title, $taxonomy ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		wp_delete_post( $existing->ID, true );
	}

	return wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => tqp_seed_block_content( $taxonomy ),
		)
	);
}

// Pretty permalinks are required for the plugin's `{tax}-page/N` endpoint.
if ( get_option( 'permalink_structure' ) !== '/%postname%/' ) {
	update_option( 'permalink_structure', '/%postname%/' );
}

// Hierarchical taxonomy fixture: 23 categories (+ default Uncategorized = 24).
for ( $i = 1; $i <= 23; $i++ ) {
	$name = sprintf( 'Category %02d', $i );
	if ( ! term_exists( $name, 'category' ) ) {
		wp_insert_term( $name, 'category' );
	}
}

// Non-hierarchical taxonomy fixture: 15 tags.
for ( $i = 1; $i <= 15; $i++ ) {
	$name = sprintf( 'Tag %02d', $i );
	if ( ! term_exists( $name, 'post_tag' ) ) {
		wp_insert_term( $name, 'post_tag' );
	}
}

$cat_page = tqp_seed_page( 'tqp-test', 'TQP Test', 'category' );
$tag_page = tqp_seed_page( 'tqp-test-tags', 'TQP Test Tags', 'post_tag' );

// Re-register the plugin's endpoints and flush so the new rewrite rules
// (and pretty permalink structure) take effect for this run.
if ( function_exists( 'terms_query_pagination_add_taxonomy_page_rewrite' ) ) {
	terms_query_pagination_add_taxonomy_page_rewrite();
}
flush_rewrite_rules();

$cats = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
$tags = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );

if ( is_wp_error( $cat_page ) || ! $cat_page || is_wp_error( $tag_page ) || ! $tag_page ) {
	fwrite( STDERR, "TQP_SEED_FAIL: page creation failed\n" );
	exit( 1 );
}
if ( (int) $cats !== 24 || (int) $tags !== 15 ) {
	fwrite( STDERR, "TQP_SEED_FAIL: expected 24 categories / 15 tags, got $cats / $tags\n" );
	exit( 1 );
}

echo "TQP_SEED_OK categories=$cats tags=$tags cat_page=$cat_page tag_page=$tag_page\n";
