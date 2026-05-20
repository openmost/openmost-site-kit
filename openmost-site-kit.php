<?php
/**
 * Matomo Site Kit
 *
 * @package           Openmost_Site_Kit
 * @author            Openmost
 * @copyright         2024 Openmost
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Matomo Site Kit
 * Plugin URI:        https://openmost.io/openmost-site-kit
 * Description:       A complete Matomo integration for WordPress with dashboard, data layer and code injection.
 * Version:           2.3.2
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Openmost
 * Author URI:        https://openmost.io
 * Text Domain:       openmost-site-kit
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'OPENMOSTSITEKIT_VERSION' ) ) {
	define( 'OPENMOSTSITEKIT_VERSION', '2.3.2' );
}
if ( ! defined( 'OPENMOSTSITEKIT_PHP_MINIMUM' ) ) {
	define( 'OPENMOSTSITEKIT_PHP_MINIMUM', '8.2.0' );
}
if ( ! defined( 'OPENMOSTSITEKIT_WP_MINIMUM' ) ) {
	define( 'OPENMOSTSITEKIT_WP_MINIMUM', '6.0.0' );
}
if ( ! defined( 'OPENMOSTSITEKIT_PLUGIN_DIR' ) ) {
	define( 'OPENMOSTSITEKIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'OPENMOSTSITEKIT_PLUGIN_FILE' ) ) {
	define( 'OPENMOSTSITEKIT_PLUGIN_FILE', __FILE__ );
}


/**
 * Register admin menu.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_register_menu_option() {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Icon is static SVG, no security concern.
	$icon_base64 = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTAiIGhlaWdodD0iMTUyIiB2aWV3Qm94PSIwIDAgMjUwIDE1MiIgZmlsbD0ibm9uZSI+CiAgPHBhdGggZD0iTTI0NC4zODQgODguMzkzNkwyMDUuNDQ0IDIxLjE2MzRDMTg4LjI5MyAtMTAuNzEzNSAxMzcuNzA0IC00LjgxOTYyIDEyNy44OTUgMjkuNjQzQzEwMC44OTMgLTMxLjQwMzMgMTYuNDcxIDEyLjAxMjIgNTEuODgzMSA2OC4zMDk2QzI1Ljc4NDggNjIuMTgxNiAtMC4zMzg4NDQgODIuOTc4MSAwLjAwMzMyNTA1IDEwOS43NkMtMC40ODE3NTMgMTU3Ljc1MSA2OS42OTM4IDE2Ny44MzkgODMuMjY2NCAxMjIuNDlDOTkuNjExNSAxNjEuMjM4IDE1MS4zNTkgMTYyLjUwNSAxNjUuNiAxMjIuMjcxQzE5MS4xNTEgMTgwLjQ0IDI3MS44MjEgMTQ0LjYzNSAyNDQuMzg0IDg4LjM5MzZaTTQyLjYxNTIgMTM4LjQ5MkM0LjczMTY3IDEzNy44MTUgNC43MzY3NCA4MS42OTU1IDQyLjYxNTIgODEuMDI4OUM4MC40OTg3IDgxLjcwNTggODAuNDkzNiAxMzcuODI1IDQyLjYxNTIgMTM4LjQ5MlpNMTQ3LjI4NCA5MC44ODc2QzE0OC43OCA5My40ODg1IDE1MC4wOTggOTYuMTk2MyAxNTEuMTM5IDk5LjAxMDhDMTU1LjkyNCAxMTEuOTM0IDE1NS4wMiAxMjQuNzU1IDEzOS4zODQgMTM0LjQzNUMxMjYuMTIzIDE0Mi40MzEgMTA3LjQ3NSAxMzcuNDU0IDEwMC4wMDUgMTIzLjkyTDYxLjA2NDIgNTYuNjg5N0M1Ny4yMTQxIDUwLjA0MjYgNTYuMTkyOSA0Mi4zMDEyIDU4LjE4NDMgMzQuODg1M0M2NC4zMzIyIDEwLjQ1OTggOTguNzI4MSA1Ljk1NTI5IDExMC45OTQgMjcuOTU4MkMxMTAuOTk0IDI3Ljk1ODIgMTMxLjQxNCA2My4yNDAyIDEzMS42MzMgNjMuNjQ3NEwxNDcuMjc5IDkwLjg5MjdMMTQ3LjI4NCA5MC44ODc2Wk0xMzkuNzI3IDQyLjQyMzNDMTQwLjM3NSA0LjY2MjYgMTk2LjcyOCA0LjY2NzY0IDE5Ny4zNjYgNDIuNDIzM0MxOTYuNzE4IDgwLjE4MzkgMTQwLjM2NSA4MC4xNzg4IDEzOS43MjcgNDIuNDIzM1pNMjM1LjMyNyAxMTcuMDY5QzIzMS42MjQgMTMyLjA2OSAyMTQuOTA3IDE0MS42OTMgMjAwLjAyMiAxMzcuMzg3QzE5Mi41ODcgMTM1LjQwMiAxODYuMzY3IDEzMC42NDkgMTgyLjUxOCAxMjQuMDAxQzE4MC41NTIgMTIwLjY0NyAxNjAuNTk2IDg2LjEzMzkgMTU5LjMwNCA4My44NzM5QzE4MC43MTQgODguOTQzMyAyMDMuMTcyIDc1LjM4NDQgMjA5LjE5OCA1NS4xMzIzTDIzMi40NDcgOTUuMjY5OEMyMzYuMjk2IDEwMS45MTcgMjM3LjMxOCAxMDkuNjU5IDIzNS4zMjcgMTE3LjA3NFYxMTcuMDY5WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+';

	add_menu_page(
		__( 'Site Kit', 'openmost-site-kit' ),
		__( 'Site Kit', 'openmost-site-kit' ),
		'edit_posts',
		'openmost-site-kit',
		'__return_null',
		'data:image/svg+xml;base64,' . $icon_base64,
		2
	);
}
add_action( 'admin_menu', 'omsk_register_menu_option' );

// Load plugin text domain.
add_action( 'init', 'omsk_load_textdomain' );

/**
 * Load plugin text domain for translations.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_load_textdomain() {
	load_plugin_textdomain( 'openmost-site-kit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Include core files.
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'includes/assets.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'includes/helpers.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'includes/rest-api.php';

// Include modules.
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/dashboard/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/post-type-charts/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/privacy/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/settings/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/tracking/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/server-tracking/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/search-tracking/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/woocommerce/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/annotations/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/wp-dashboard-widget/index.php';
