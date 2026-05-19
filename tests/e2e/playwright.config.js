const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: __dirname,
	globalSetup: require.resolve( './global-setup.js' ),
	timeout: 30000,
	fullyParallel: false,
	reporter: [ [ 'list' ] ],
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'retain-on-failure',
		launchOptions: {
			executablePath:
				process.env.PW_CHROMIUM ||
				`${ process.env.HOME }/.cache/ms-playwright/chromium-1208/chrome-linux64/chrome`,
		},
	},
	projects: [
		{ name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } },
	],
} );
