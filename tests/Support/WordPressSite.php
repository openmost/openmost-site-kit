<?php
/**
 * Real WordPress site for integration tests.
 *
 * Builds a throwaway site under tests/.tmp/site (wp-config.php, minimal theme,
 * a copy of the plugin, a fake advanced-cache.php) on top of an existing
 * WordPress core, installs it in a dedicated database and serves it with the
 * PHP built-in server.
 *
 * The plugin is copied, not linked: track.php locates wp-config.php from its
 * own real path, exactly like on a production install.
 *
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Support;

use mysqli;
use RuntimeException;

final class WordPressSite {

    private const PLUGIN_ENTRIES = array( 'openmost-site-kit.php', 'includes', 'modules', 'languages', 'build' );

    private string $root;

    private BackgroundServer $server;

    public function __construct( string $root ) {
        $this->root = $root;

        $core = realpath( __DIR__ . '/../' . getenv( 'OMSK_TEST_WP_CORE' ) );
        if ( false === $core || ! is_file( $core . '/wp-settings.php' ) ) {
            throw new RuntimeException( 'OMSK_TEST_WP_CORE does not point to a WordPress core.' );
        }

        self::remove( $root );
        self::copy( __DIR__ . '/../Integration/fixture', $root );
        mkdir( $root . '/flags', 0777, true );

        foreach ( self::PLUGIN_ENTRIES as $entry ) {
            self::copy( OMSK_TESTS_PLUGIN_DIR . $entry, $root . '/wp-content/plugins/openmost-site-kit/' . $entry );
        }

        $this->reset_database();
        $this->write_wp_config( str_replace( '\\', '/', $core ) . '/' );

        $this->server = new BackgroundServer( $root, $root . '/index.php' );
        file_put_contents( $root . '/flags/host', substr( $this->server->url(), strlen( 'http://' ) ) );

        $this->cli( 'install' );
    }

    public function url( string $path = '/' ): string {
        return $this->server->url() . $path;
    }

    public function root(): string {
        return $this->root;
    }

    public function plugin_file( string $relative ): string {
        return $this->root . '/wp-content/plugins/openmost-site-kit/' . $relative;
    }

    /**
     * @param array{settings?: array|null, defaultApplied?: bool, active?: bool} $state
     */
    public function set_state( array $state ): void {
        $this->cli( 'state', $state );
    }

    public function option( string $name ) {
        return json_decode( $this->cli( 'get-option', array( 'name' => $name ) ), true );
    }

    public function cache_status(): ?array {
        return json_decode( $this->cli( 'cache-status' ), true );
    }

    /**
     * WP_CACHE on or off, and the page served by the fake advanced-cache.php (null = cache miss).
     */
    public function set_page_cache( bool $wp_cache, ?string $cached_html = null ): void {
        $this->flag( 'wp-cache', $wp_cache ? '1' : null );
        $this->flag( 'cache-hit', $cached_html );
    }

    public function set_dropin( bool $installed ): void {
        $this->flag( 'dropin', $installed ? '1' : null );
    }

    /**
     * @return array{status: int, body: string, headers: string}
     */
    public function get( string $path, string $user_agent, array $cookies = array(), string $method = 'GET' ): array {
        $curl = curl_init( $this->url( $path ) );
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_USERAGENT      => $user_agent,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => false,
            )
        );

        if ( $cookies ) {
            curl_setopt( $curl, CURLOPT_COOKIE, http_build_query( $cookies, '', '; ' ) );
        }

        $response = (string) curl_exec( $curl );
        $status   = (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
        $size     = (int) curl_getinfo( $curl, CURLINFO_HEADER_SIZE );

        return array(
            'status'  => $status,
            'headers' => substr( $response, 0, $size ),
            'body'    => substr( $response, $size ),
        );
    }

    public function stop(): void {
        $this->server->stop();
    }

    private function flag( string $name, ?string $content ): void {
        $file = $this->root . '/flags/' . $name;

        if ( null === $content ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
            return;
        }

        file_put_contents( $file, $content );
    }

    private function cli( string $command, array $payload = array() ): string {
        $process = proc_open(
            array( PHP_BINARY, $this->root . '/cli.php', $command, json_encode( $payload ) ),
            array(
                1 => array( 'pipe', 'w' ),
                2 => array( 'pipe', 'w' ),
            ),
            $pipes,
            $this->root
        );

        $output = stream_get_contents( $pipes[1] );
        $errors = stream_get_contents( $pipes[2] );
        fclose( $pipes[1] );
        fclose( $pipes[2] );

        if ( 0 !== proc_close( $process ) ) {
            throw new RuntimeException( "cli.php {$command} failed: {$output}{$errors}" );
        }

        return (string) $output;
    }

    private function reset_database(): void {
        $db = new mysqli( getenv( 'OMSK_TEST_DB_HOST' ), getenv( 'OMSK_TEST_DB_USER' ), (string) getenv( 'OMSK_TEST_DB_PASSWORD' ) );
        $name = $db->real_escape_string( (string) getenv( 'OMSK_TEST_DB_NAME' ) );

        if ( ! preg_match( '/_tests?$/', $name ) ) {
            throw new RuntimeException( 'Refusing to drop a database whose name does not end with _test or _tests.' );
        }

        $db->query( "DROP DATABASE IF EXISTS `{$name}`" );
        $db->query( "CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );
        $db->close();
    }

    private function write_wp_config( string $core ): void {
        $config = array(
            'DB_NAME'     => getenv( 'OMSK_TEST_DB_NAME' ),
            'DB_USER'     => getenv( 'OMSK_TEST_DB_USER' ),
            'DB_PASSWORD' => (string) getenv( 'OMSK_TEST_DB_PASSWORD' ),
            'DB_HOST'     => getenv( 'OMSK_TEST_DB_HOST' ),
        );

        $defines = '';
        foreach ( $config as $name => $value ) {
            $defines .= "define( '{$name}', " . var_export( $value, true ) . " );\n";
        }

        $track = $this->plugin_file( 'modules/ai-bot-tracking/track.php' );

        file_put_contents(
            $this->root . '/wp-config.php',
            "<?php\n"
            . $defines
            . "define( 'DB_CHARSET', 'utf8mb4' );\n"
            . "define( 'DB_COLLATE', '' );\n"
            . "\$table_prefix = 'wp_';\n"
            . "define( 'WP_CACHE', is_file( __DIR__ . '/flags/wp-cache' ) );\n"
            . "define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );\n"
            . "define( 'WP_HOME', 'http://' . ( \$_SERVER['HTTP_HOST'] ?? '' ) );\n"
            . "define( 'WP_SITEURL', WP_HOME );\n"
            . "define( 'WP_CONTENT_URL', WP_HOME . '/wp-content' );\n"
            . "define( 'WP_DEBUG', false );\n"
            . "define( 'DISABLE_WP_CRON', true );\n"
            . "define( 'AUTOMATIC_UPDATER_DISABLED', true );\n"
            . "if ( ! defined( 'ABSPATH' ) ) {\n\tdefine( 'ABSPATH', " . var_export( $core, true ) . " );\n}\n"
            . "if ( is_file( __DIR__ . '/flags/dropin' ) ) {\n"
            . "\tif ( is_file( " . var_export( $track, true ) . " ) ) {\n\t\trequire_once " . var_export( $track, true ) . ";\n\t}\n"
            . "}\n"
            . "require_once ABSPATH . 'wp-settings.php';\n"
        );
    }

    private static function copy( string $source, string $target ): void {
        if ( is_file( $source ) ) {
            if ( ! is_dir( dirname( $target ) ) ) {
                mkdir( dirname( $target ), 0777, true );
            }
            copy( $source, $target );
            return;
        }

        if ( ! is_dir( $source ) ) {
            return;
        }

        if ( ! is_dir( $target ) ) {
            mkdir( $target, 0777, true );
        }

        foreach ( scandir( $source ) as $entry ) {
            if ( '.' !== $entry && '..' !== $entry ) {
                self::copy( $source . '/' . $entry, $target . '/' . $entry );
            }
        }
    }

    private static function remove( string $path ): void {
        if ( is_file( $path ) || is_link( $path ) ) {
            unlink( $path );
            return;
        }

        if ( ! is_dir( $path ) ) {
            return;
        }

        foreach ( scandir( $path ) as $entry ) {
            if ( '.' !== $entry && '..' !== $entry ) {
                self::remove( $path . '/' . $entry );
            }
        }

        rmdir( $path );
    }
}
