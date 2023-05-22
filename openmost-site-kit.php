<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name: Matomo Site Kit
 * Plugin URI: https://openmost.io/openmost-site-kit
 * Description: A complete Matomo integration for WordPress with dashboard, data layer and code injection.
 * Author: Openmost
 * Version: 1.0.2
 * Author URI: https://openmost.io
 */

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Constant
define( 'OPENMOSTSITEKIT_VERSION', '1.0.2' );
define( 'OPENMOSTSITEKIT_PHP_MINIMUM', '5.6.0' );
define( 'OPENMOSTSITEKIT_WP_MINIMUM', '5.2.0' );
define( 'OPENMOSTSITEKIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


function omsk_register_menu_option() {

	$icon_base64 = 'PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xOS41NTc4IDEwLjcwNzFMMTkuNTQ4MiAxMC43MTQxTDE5LjQ5NjkgMTAuNjM1OUMxOS40ODczIDEwLjYyNTggMTkuNDc5MSAxMC42MTQ1IDE5LjQ3MjYgMTAuNjAyNkwxNS45Njg4IDUuNDE0NzNDMTUuOTc4OSA1LjQzMjA3IDE1Ljk4MiA1LjQ1MjIyIDE1Ljk5MTkgNS40Njk1NkMxNS45NDk1IDUuMzkzNjQgMTUuOTEwMyA1LjMxNjA5IDE1Ljg2MDQgNS4yNDM5M0MxNS41NDM4IDQuNzgzMjkgMTUuMDkzOCA0LjQyNDM0IDE0LjU2NzUgNC4yMTI1NEMxNC4wNDEyIDQuMDAwNzMgMTMuNDYyMiAzLjk0NTY3IDEyLjkwMzggNC4wNTQzOEMxMi4zNDU1IDQuMTYzMSAxMS44MzI3IDQuNDMwNDQgMTEuNDMwNCA0LjgyMjg5QzExLjAyOCA1LjIxNTM0IDEwLjc1NDUgNS43MTQ4NyAxMC42NDQ1IDYuMjU4NjhDMTAuNTM0MiA2LjgwMjQ5IDEwLjU5MjMgNy4zNjU3NSAxMC44MTE0IDcuODc3MjJDMTAuODk5IDguMDgxNzcgMTEuMDI3MSA4LjI2MzU4IDExLjE2IDguNDQwNzFDMTEuMTMyOCA4LjQwNDQgMTEuMDk1OSA4LjM3NjA1IDExLjA3MDcgOC4zMzgzMkw4Ljg3NTYyIDUuMzMzNjZDOC42MTg5NyA0LjkyNjIxIDguMjU5MjcgNC41ODk3NiA3LjgzMDcxIDQuMzU2MTZDNy40MDE5MSA0LjEyMjggNi45MTg5NCA0LjAwMDI2IDYuNDI4MDIgNC4wMDAyNkM1LjkzNzEgNC4wMDAyNiA1LjQ1NDEzIDQuMTIyOCA1LjAyNTU3IDQuMzU2NEM0LjU5NzAxIDQuNTg5OTkgNC4yMzczMSA0LjkyNjQ1IDMuOTgwNjYgNS4zMzM5TDAuNDkyNzE4IDEwLjYxMjJDMC41MDIxMDggMTAuNTk4OSAwLjUxNTgzMiAxMC41ODkgMC41MjU0NjIgMTAuNTc1OUMwLjI5Njk3OCAxMC44OTI5IDAuMTMzNzQgMTEuMjUxOCAwLjA1NTI1MSAxMS42MzQ5Qy0wLjA1NTc0MSAxMi4xNzgzIDAuMDAxMzE5OTYgMTIuNzQxMyAwLjIxODk3IDEzLjI1M0MwLjQzNjg2MSAxMy43NjQ3IDAuODA1NzExIDE0LjIwMjIgMS4yNzkwNSAxNC41MDk4QzEuNzUyMzkgMTQuODE3NCAyLjMwODggMTQuOTgxOSAyLjg3Nzk2IDE0Ljk4MTlDMy42NDExOCAxNC45ODE5IDQuMzczMzUgMTQuNjg2OSA0LjkxMjkgMTQuMTYxNkM1LjQ1MjY5IDEzLjYzNjYgNS43NTU4MSAxMi45MjQgNS43NTU4MSAxMi4xODE1QzUuNzU1ODEgMTEuNjYwMiA1LjYwMTI0IDExLjE1MjcgNS4zMjAyNyAxMC43MTA2QzUuMzI1NTYgMTAuNzE4OCA1LjMzMzc1IDEwLjcyNTEgNS4zMzg4MSAxMC43MzM2TDcuNjIxNzMgMTMuODQwNEM3Ljg4ODI1IDE0LjE5NDkgOC4yMzY4OCAxNC40ODMzIDguNjM5NjcgMTQuNjgyMkM5LjA0MjIzIDE0Ljg4MTIgOS40ODc0IDE0Ljk4NDcgOS45Mzg4MyAxNC45ODQ3QzEwLjM5MDMgMTQuOTg0NyAxMC44MzU0IDE0Ljg4MTIgMTEuMjM4IDE0LjY4MjJDMTEuNjQwNiAxNC40ODM1IDExLjk4OTQgMTQuMTk0OSAxMi4yNTU5IDEzLjg0MDRMMTIuMjc3OSAxMy44MDQ4TDEyLjQ0MzcgMTMuNTY3NEwxMy41MTUxIDExLjk0MDlMMTQuNjk0NiAxMy43MDI5TDE0LjczNiAxMy43NjcxTDE0Ljc1NTUgMTMuNzkzMUMxNS4xODM2IDE0LjM5NTIgMTUuODM3MSAxNC44MTAyIDE2LjU3NTcgMTQuOTQ5MUMxNy4zMTQ2IDE1LjA4OCAxOC4wNzk4IDE0Ljk0IDE4LjcwNzcgMTQuNTM2NUMxOS4zMzU2IDE0LjEzMyAxOS43NzYyIDEzLjUwNjMgMTkuOTM1MSAxMi43OTA3QzIwLjA5NCAxMi4wNzQ5IDE5Ljk1ODUgMTEuMzI3MyAxOS41NTc4IDEwLjcwNzZWMTAuNzA3MVpNNC4yMDYwMSAxMy40NzMyQzMuODUzNzggMTMuODE2IDMuMzc2MSAxNC4wMDg2IDIuODc3OTYgMTQuMDA4NkMyLjUwNjQ2IDE0LjAwODYgMi4xNDMzOSAxMy45MDE1IDEuODM0NzMgMTMuNzAwNUMxLjUyNTgzIDEzLjQ5OTcgMS4yODUzMSAxMy4yMTQzIDEuMTQzMDIgMTIuODgwNUMxLjAwMDk3IDEyLjU0NjYgMC45NjM2NTIgMTIuMTc5MiAxLjAzNjEyIDExLjgyNDdDMS4xMDg1OSAxMS40NzAyIDEuMjg3NDggMTEuMTQ0NSAxLjU1MDE1IDEwLjg4OTFDMS44MTI4MiAxMC42MzM1IDIuMTQ3NDkgMTAuNDU5NCAyLjUxMTc2IDEwLjM4ODlDMi44NzYwNCAxMC4zMTg0IDMuMjUzNzkgMTAuMzU0NSAzLjU5Njg4IDEwLjQ5MjlDMy45Mzk5NyAxMC42MzEyIDQuMjMzMjIgMTAuODY1NSA0LjQzOTU1IDExLjE2NjFDNC42NDU4OSAxMS40NjY3IDQuNzU1OTIgMTEuODE5OCA0Ljc1NTkyIDEyLjE4MTNDNC43NTU5MiAxMi42NjU4IDQuNTU4MDEgMTMuMTMwNyA0LjIwNjAxIDEzLjQ3MzVWMTMuNDczMlpNMTQuODAzMiA4LjA5Mjc4QzE0LjQ1MSA4LjQzNTU2IDEzLjk3MzMgOC42MjgxNSAxMy40NzUyIDguNjI4MTVIMTMuNDcyNUMxMy4xMDA4IDguNjI5MDkgMTIuNzM3NSA4LjUyMjcyIDEyLjQyNzggOC4zMjIzOUMxMi4xMTg1IDguMTIyMDcgMTEuODc3IDcuODM3MTYgMTEuNzM0MiA3LjUwMzI4QzExLjU5MTQgNy4xNjk0IDExLjU1MzQgNi44MDIwMiAxMS42MjU0IDYuNDQ3MDZDMTEuNjk3NCA2LjA5MjMzIDExLjg3NTggNS43NjYxOCAxMi4xMzgyIDUuNTEwMDlDMTIuNDAwNiA1LjI1NCAxMi43MzUzIDUuMDc5NjggMTMuMDk5NiA1LjAwODY5QzEzLjQ2NDEgNC45Mzc5MyAxMy44NDE5IDQuOTczNzggMTQuMTg1MiA1LjExMjAxQzE0LjUyODggNS4yNTAyNSAxNC44MjIyIDUuNDg0NTUgMTUuMDI4OCA1Ljc4NTE2QzE1LjIzNTQgNi4wODU3NiAxNS4zNDU3IDYuNDM5MzIgMTUuMzQ1NyA2LjgwMDg1SDE1LjM1MjlDMTUuMzUyOSA3LjI4NTYyIDE1LjE1NSA3Ljc1MDIzIDE0LjgwMyA4LjA5MzAxTDE0LjgwMzIgOC4wOTI3OFoiCiAgICAgICAgICBmaWxsPSIjQTdBQUFEIi8+Cjwvc3ZnPgo=';

	add_menu_page(
		__( 'Site Kit', 'openmost-site-kit' ),
		__( 'Site Kit', 'openmost-site-kit' ),
		'manage_options',
		'openmost-site-kit',
		false,
		'data:image/svg+xml;base64,' . $icon_base64,
		2
	);
}

add_action( 'admin_menu', 'omsk_register_menu_option' );

require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'includes/assets.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'includes/helpers.php';

// Modules
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/dashboard/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/post-type-charts/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/privacy/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/settings/index.php';



add_action( 'plugins_loaded', 'omsk_load_textdomain' );

function omsk_load_textdomain() {

	$domain = 'openmost-site-kit';
	load_plugin_textdomain( $domain, false, dirname( __FILE__ ) . '/languages/' );

}