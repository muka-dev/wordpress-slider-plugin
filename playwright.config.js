/**
 * Playwright configuration, based on the @wordpress/scripts default.
 *
 * `WP_BASE_URL` defaults to the wp-env test instance (http://localhost:8889).
 */
const { defineConfig, devices } = require( '@playwright/test' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e',
	webServer: {
		...baseConfig.webServer,
		command: 'npm run env:start',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{
			name: 'mobile',
			use: { ...devices[ 'Pixel 7' ] },
		},
	],
} );
