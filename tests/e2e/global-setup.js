const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );

const REPO_ROOT = path.resolve( __dirname, '../..' );
const WP_ENV = path.join( REPO_ROOT, 'node_modules/.bin/wp-env' );

// Path to seed.php as seen from inside the wp-env cli container, where the
// plugin is mounted at wp-content/plugins/terms-query-pagination.
const SEED_IN_CONTAINER =
	'wp-content/plugins/terms-query-pagination/tests/e2e/seed.php';

/**
 * Seed the wp-env database before the suite runs so the specs are
 * self-contained (no manual wp-cli setup required).
 */
module.exports = async function globalSetup() {
	let output;
	try {
		output = execFileSync(
			WP_ENV,
			[ 'run', 'cli', 'wp', 'eval-file', SEED_IN_CONTAINER ],
			{ cwd: REPO_ROOT, encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ] }
		);
	} catch ( err ) {
		const detail = `${ err.stdout || '' }${ err.stderr || '' }`;
		throw new Error(
			`e2e global-setup: seeding failed.\n` +
				`Is wp-env running? Try \`npm run env:start\`.\n${ detail }`
		);
	}

	if ( ! output.includes( 'TQP_SEED_OK' ) ) {
		throw new Error( `e2e global-setup: unexpected seed output:\n${ output }` );
	}

	const summary = output.match( /TQP_SEED_OK[^\n]*/ );
	// eslint-disable-next-line no-console
	console.log( `[e2e] ${ summary ? summary[ 0 ] : 'seeded' }` );
};
