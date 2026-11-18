<?php
/**
 * PHP built-in web server running in the background for the duration of a test class.
 *
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Support;

use RuntimeException;

final class BackgroundServer {

    /** @var resource */
    private $process;

    private int $port;

    public function __construct( string $doc_root, ?string $router = null ) {
        $this->port = self::free_port();

        $command = array( PHP_BINARY, '-S', '127.0.0.1:' . $this->port, '-t', $doc_root );
        if ( null !== $router ) {
            $command[] = $router;
        }

        $null          = 'Windows' === PHP_OS_FAMILY ? 'NUL' : '/dev/null';
        $this->process = proc_open(
            $command,
            array(
                0 => array( 'file', $null, 'r' ),
                1 => array( 'file', $null, 'w' ),
                2 => array( 'file', $null, 'w' ),
            ),
            $pipes,
            $doc_root
        );

        if ( ! is_resource( $this->process ) ) {
            throw new RuntimeException( 'Could not start the PHP built-in server.' );
        }

        $this->wait_until_listening();
    }

    public function url(): string {
        return 'http://127.0.0.1:' . $this->port;
    }

    public function stop(): void {
        if ( is_resource( $this->process ) ) {
            proc_terminate( $this->process );
            proc_close( $this->process );
        }
    }

    private function wait_until_listening(): void {
        $deadline = microtime( true ) + 10;

        while ( microtime( true ) < $deadline ) {
            $socket = @fsockopen( '127.0.0.1', $this->port, $errno, $errstr, 0.2 );
            if ( $socket ) {
                fclose( $socket );
                return;
            }
            usleep( 100000 );
        }

        throw new RuntimeException( 'The PHP built-in server did not start on port ' . $this->port . '.' );
    }

    private static function free_port(): int {
        $socket = stream_socket_server( 'tcp://127.0.0.1:0', $errno, $errstr );
        $name   = stream_socket_get_name( $socket, false );
        fclose( $socket );

        return (int) substr( (string) strrchr( (string) $name, ':' ), 1 );
    }
}
