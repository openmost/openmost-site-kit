<?php
/**
 * Local HTTP endpoint standing in for matomo.php, records every tracking request.
 *
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Support;

final class FakeMatomo {

    private BackgroundServer $server;

    private string $log_file;

    public function __construct( string $work_dir ) {
        if ( ! is_dir( $work_dir ) ) {
            mkdir( $work_dir, 0777, true );
        }

        $this->log_file = $work_dir . '/hits.jsonl';
        file_put_contents(
            $work_dir . '/router.php',
            '<?php file_put_contents(' . var_export( $this->log_file, true ) . ', json_encode(array('
            . '"query" => $_SERVER["QUERY_STRING"] ?? "", '
            . '"ua" => $_SERVER["HTTP_USER_AGENT"] ?? "", '
            . '"method" => $_SERVER["REQUEST_METHOD"] ?? "")) . "\n", FILE_APPEND | LOCK_EX); echo "ok";'
        );

        $this->reset();
        $this->server = new BackgroundServer( $work_dir, $work_dir . '/router.php' );
    }

    public function url(): string {
        return $this->server->url();
    }

    public function reset(): void {
        file_put_contents( $this->log_file, '' );
    }

    /**
     * @return array<int, array{params: array<string, string>, ua: string}>
     */
    public function hits( int $expected = 0, float $timeout = 3.0 ): array {
        $deadline = microtime( true ) + $timeout;

        do {
            $hits = $this->read();
            if ( $expected > 0 && count( $hits ) >= $expected ) {
                return $hits;
            }
            usleep( 50000 );
        } while ( $expected > 0 && microtime( true ) < $deadline );

        // Leave room for a late request so "no hit" assertions are meaningful.
        if ( 0 === $expected ) {
            usleep( 400000 );
            $hits = $this->read();
        }

        return $hits;
    }

    private function read(): array {
        $hits = array();
        foreach ( file( $this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ?: array() as $line ) {
            $row = json_decode( $line, true );
            parse_str( (string) $row['query'], $params );
            $hits[] = array(
                'params' => $params,
                'ua'     => (string) $row['ua'],
            );
        }

        return $hits;
    }

    public function stop(): void {
        $this->server->stop();
    }
}
