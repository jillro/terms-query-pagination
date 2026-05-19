#!/usr/bin/env node
/**
 * Fail when the plugin version is not identical across all the places that
 * carry it. WordPress.org, npm and the plugin runtime each read a different
 * one, so a mismatch ships a plugin that lies about its own version.
 *
 * Checked:
 *   - package.json                 → "version"
 *   - readme.txt                   → "Stable tag:"
 *   - terms-query-pagination.php   → "* Version:" plugin header
 *   - terms-query-pagination.php   → TERMS_QUERY_PAGINATION_VERSION define()
 *
 * Run via `npm run version:check` (used in CI).
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const read = ( name ) => readFileSync( join( root, name ), 'utf8' );

const find = ( label, file, regex ) => {
	const match = read( file ).match( regex );
	if ( ! match ) {
		process.stderr.write(
			`Could not find a version in ${ file } (${ label }).\n`
		);
		process.exit( 1 );
	}
	return { label: `${ file } (${ label })`, version: match[ 1 ].trim() };
};

const sources = [
	find( 'version', 'package.json', /"version":\s*"([^"]+)"/ ),
	find( 'Stable tag', 'readme.txt', /^Stable tag:\s*(.+)\s*$/m ),
	find(
		'plugin header',
		'terms-query-pagination.php',
		/^\s*\*\s*Version:\s*(.+)\s*$/m
	),
	find(
		'VERSION define',
		'terms-query-pagination.php',
		/TERMS_QUERY_PAGINATION_VERSION',\s*'([^']+)'/
	),
];

const versions = new Set( sources.map( ( s ) => s.version ) );

if ( versions.size === 1 ) {
	process.stdout.write(
		`Plugin version is consistent: ${ [ ...versions ][ 0 ] }\n`
	);
	process.exit( 0 );
}

process.stderr.write( 'Plugin version mismatch:\n' );
for ( const { label, version } of sources ) {
	process.stderr.write( `  ${ version }  ←  ${ label }\n` );
}
process.stderr.write( '\nMake all of the above identical, then commit.\n' );
process.exit( 1 );
