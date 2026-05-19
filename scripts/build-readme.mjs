#!/usr/bin/env node
/**
 * Generate README.md from readme.txt (the WordPress.org source of truth).
 *
 * readme.txt stays canonical: it carries the required `Stable tag`,
 * `Tested up to`, etc. and is what WordPress.org parses. README.md is the
 * GitHub-facing, prettier rendering and must never be hand-edited — edit
 * readme.txt (synced body) or the partials in scripts/readme-parts/
 * (hand-written header/footer), then run `npm run readme`.
 *
 * Run `npm run readme:check` (used in CI) to fail when the two drift.
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const partsDir = join( root, 'scripts', 'readme-parts' );

const source = readFileSync( join( root, 'readme.txt' ), 'utf8' );
const lines = source.split( /\r?\n/ );

// --- Parse the plugin header (everything before the first `==` section). ---
const titleMatch = source.match( /^===\s*(.+?)\s*===\s*$/m );
const title = titleMatch ? titleMatch[ 1 ] : 'Plugin';

const headers = {};
let shortDescription = '';
let bodyStart = lines.length;

for ( let i = 0; i < lines.length; i++ ) {
	const line = lines[ i ];
	if ( /^===\s*.+\s*===\s*$/.test( line ) ) {
		continue;
	}
	if ( /^==\s*.+\s*==\s*$/.test( line ) ) {
		bodyStart = i;
		break;
	}
	const kv = line.match( /^([A-Za-z][A-Za-z .]+):\s*(.+)\s*$/ );
	if ( kv ) {
		headers[ kv[ 1 ].trim() ] = kv[ 2 ].trim();
	} else if ( line.trim() !== '' ) {
		// First non-empty, non-header line is the short description.
		shortDescription = shortDescription
			? `${ shortDescription } ${ line.trim() }`
			: line.trim();
	}
}

// --- Convert the section body to Markdown. ---
const bodyMd = lines
	.slice( bodyStart )
	.join( '\n' )
	.replace( /^==\s*(.+?)\s*==\s*$/gm, '## $1' )
	.replace( /^=\s*(.+?)\s*=\s*$/gm, '### $1' )
	.trim();

// --- Build badges from the parsed header metadata. ---
const badge = ( label, message, color ) =>
	`![${ label }](https://img.shields.io/badge/${ encodeURIComponent(
		label
	) }-${ encodeURIComponent(
		String( message ).replace( /-/g, '--' )
	) }-${ color })`;

const badges = [];
if ( headers[ 'Stable tag' ] ) {
	badges.push( badge( 'Stable', headers[ 'Stable tag' ], '0a7caf' ) );
}
if ( headers[ 'Tested up to' ] ) {
	badges.push(
		badge( 'WordPress', `${ headers[ 'Tested up to' ] } tested`, '21759b' )
	);
}
if ( headers[ 'Requires PHP' ] ) {
	badges.push( badge( 'PHP', `${ headers[ 'Requires PHP' ] }+`, '777bb4' ) );
}
if ( headers.License ) {
	badges.push( badge( 'License', headers.License, 'green' ) );
}

const readPart = ( name ) => {
	const path = join( partsDir, name );
	if ( ! existsSync( path ) ) {
		return '';
	}
	// Strip leading HTML comment blocks: they hold editor guidance for the
	// partial and must not surface in the rendered README.
	return readFileSync( path, 'utf8' )
		.replace( /^\s*(?:<!--[\s\S]*?-->\s*)+/, '' )
		.trim();
};

const header = readPart( 'header.md' );
const footer = readPart( 'footer.md' );

// --- Assemble. Each block is separated by a blank line. ---
const out =
	[
		'<!-- AUTO-GENERATED FROM readme.txt — DO NOT EDIT BY HAND. -->\n' +
			'<!-- Edit readme.txt (synced body) or scripts/readme-parts/{header,footer}.md, then run `npm run readme`. -->',
		`<h1 align="center">${ title }</h1>`,
		shortDescription
			? `<p align="center"><em>${ shortDescription }</em></p>`
			: '',
		badges.length
			? `<p align="center">\n  ${ badges.join( '\n  ' ) }\n</p>`
			: '',
		header,
		'---',
		bodyMd,
		footer ? `---\n\n${ footer }` : '',
	]
		.filter( ( part ) => part !== '' )
		.join( '\n\n' ) + '\n';

const target = join( root, 'README.md' );

if ( process.argv.includes( '--check' ) ) {
	const current = existsSync( target ) ? readFileSync( target, 'utf8' ) : '';
	if ( current !== out ) {
		process.stderr.write(
			'README.md is out of sync with readme.txt.\n' +
				'Run `npm run readme` and commit the result.\n'
		);
		process.exit( 1 );
	}
	process.stdout.write( 'README.md is in sync with readme.txt.\n' );
} else {
	writeFileSync( target, out );
	process.stdout.write( 'README.md generated from readme.txt.\n' );
}
