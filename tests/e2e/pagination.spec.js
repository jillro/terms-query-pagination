const { test, expect } = require( '@playwright/test' );

const PAGE = '/tqp-test/';
const PHP_ERROR_RE = /\b(Fatal error|Parse error|Warning|Notice|Deprecated):/;

/**
 * Attach listeners that fail the test on browser console errors,
 * uncaught page errors, or WP_DEBUG_DISPLAY PHP errors in the markup.
 */
function guard( page, sink ) {
	page.on( 'console', ( msg ) => {
		if ( msg.type() === 'error' ) {
			sink.console.push( msg.text() );
		}
	} );
	page.on( 'pageerror', ( err ) => sink.pageerror.push( err.message ) );
}

async function assertNoPhpErrors( page ) {
	const body = await page.locator( 'body' ).innerText();
	const match = body.match( PHP_ERROR_RE );
	expect( match, `PHP error rendered in page: ${ match && match[ 0 ] }` ).toBeNull();
}

test.describe( 'Terms Query Pagination', () => {
	test( 'page 1 shows first 6 terms, next + numbers, no previous', async ( { page } ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		await page.goto( PAGE );

		const nav = page.locator( 'nav[aria-label="Terms Pagination"]' );
		await expect( nav ).toBeVisible();

		await expect( page.getByText( 'Category 01' ) ).toBeVisible();
		await expect( page.getByText( 'Category 06' ) ).toBeVisible();
		await expect( page.getByText( 'Category 07' ) ).toHaveCount( 0 );

		// First page: no previous link, but a next link and numbered links.
		await expect(
			nav.locator( '.wp-block-terms-query-pagination-terms-query-pagination-previous' )
		).toHaveCount( 0 );
		await expect(
			nav.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
		).toBeVisible();
		await expect( nav.getByRole( 'link', { name: '2' } ) ).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console, 'console errors' ).toEqual( [] );
		expect( sink.pageerror, 'page errors' ).toEqual( [] );
	} );

	test( 'clicking Next goes to page 2 with pretty permalink', async ( { page } ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		await page.goto( PAGE );
		await page
			.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
			.click();

		await expect( page ).toHaveURL( /\/category-page\/2\/?$/ );
		await expect( page.getByText( 'Category 07' ) ).toBeVisible();
		await expect( page.getByText( 'Category 12' ) ).toBeVisible();
		await expect( page.getByText( 'Category 06' ) ).toHaveCount( 0 );

		// Page 2 has a previous link.
		await expect(
			page.locator( '.wp-block-terms-query-pagination-terms-query-pagination-previous' )
		).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console ).toEqual( [] );
		expect( sink.pageerror ).toEqual( [] );
	} );

	test( 'last page shows remaining terms and hides Next', async ( { page } ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		// 24 terms (Category 01..23 + default "Uncategorized") / 6 per page
		// = 4 pages; ordered by name, page 4 = Category 19..23 + Uncategorized.
		await page.goto( '/tqp-test/category-page/4/' );

		await expect( page.getByText( 'Category 19' ) ).toBeVisible();
		await expect( page.getByText( 'Category 23' ) ).toBeVisible();
		await expect( page.getByText( 'Uncategorized' ) ).toBeVisible();
		await expect( page.getByText( 'Category 18' ) ).toHaveCount( 0 );

		await expect(
			page.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
		).toHaveCount( 0 );
		await expect(
			page.locator( '.wp-block-terms-query-pagination-terms-query-pagination-previous' )
		).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console ).toEqual( [] );
		expect( sink.pageerror ).toEqual( [] );
	} );

	test( 'numbered link navigates to the chosen page', async ( { page } ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		await page.goto( PAGE );
		await page
			.locator( 'nav[aria-label="Terms Pagination"]' )
			.getByRole( 'link', { name: '3' } )
			.click();

		await expect( page ).toHaveURL( /\/category-page\/3\/?$/ );
		await expect( page.getByText( 'Category 13' ) ).toBeVisible();
		await expect( page.getByText( 'Category 18' ) ).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console ).toEqual( [] );
		expect( sink.pageerror ).toEqual( [] );
	} );
} );

// Non-hierarchical taxonomy (post_tag). Regression coverage for the core
// term-template fix shipped in WP 6.9.4: without it, core forced
// `parent = 0` on non-hierarchical taxonomies and returned zero terms.
test.describe( 'Terms Query Pagination (non-hierarchical taxonomy)', () => {
	const TAGS_PAGE = '/tqp-test-tags/';

	test( 'tag page 1 lists terms and paginates with post_tag-page permalink', async ( {
		page,
	} ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		await page.goto( TAGS_PAGE );

		const nav = page.locator( 'nav[aria-label="Terms Pagination"]' );
		await expect( nav ).toBeVisible();

		// 15 tags / 6 per page = 3 pages; page 1 = Tag 01..06.
		await expect( page.getByText( 'Tag 01' ) ).toBeVisible();
		await expect( page.getByText( 'Tag 06' ) ).toBeVisible();
		await expect( page.getByText( 'Tag 07' ) ).toHaveCount( 0 );

		await expect(
			nav.locator( '.wp-block-terms-query-pagination-terms-query-pagination-previous' )
		).toHaveCount( 0 );
		await expect(
			nav.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
		).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console ).toEqual( [] );
		expect( sink.pageerror ).toEqual( [] );
	} );

	test( 'Next walks to the last tag page and hides Next', async ( { page } ) => {
		const sink = { console: [], pageerror: [] };
		guard( page, sink );

		await page.goto( TAGS_PAGE );
		await page
			.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
			.click();

		await expect( page ).toHaveURL( /\/post_tag-page\/2\/?$/ );
		await expect( page.getByText( 'Tag 07' ) ).toBeVisible();
		await expect( page.getByText( 'Tag 12' ) ).toBeVisible();

		await page
			.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
			.click();

		// Last page (3) = Tag 13..15, no Next.
		await expect( page ).toHaveURL( /\/post_tag-page\/3\/?$/ );
		await expect( page.getByText( 'Tag 13' ) ).toBeVisible();
		await expect( page.getByText( 'Tag 15' ) ).toBeVisible();
		await expect(
			page.locator( '.wp-block-terms-query-pagination-terms-query-pagination-next' )
		).toHaveCount( 0 );
		await expect(
			page.locator( '.wp-block-terms-query-pagination-terms-query-pagination-previous' )
		).toBeVisible();

		await assertNoPhpErrors( page );
		expect( sink.console ).toEqual( [] );
		expect( sink.pageerror ).toEqual( [] );
	} );
} );
