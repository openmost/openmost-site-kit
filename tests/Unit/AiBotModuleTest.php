<?php
/**
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Unit;

use Openmost\SiteKit\Tests\Support\FakeMatomo;
use PHPUnit\Framework\TestCase;

final class AiBotModuleTest extends TestCase {

    private const CHATGPT = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot';

    private static FakeMatomo $matomo;

    public static function setUpBeforeClass(): void {
        self::$matomo = new FakeMatomo( OMSK_TESTS_TMP_DIR . '/unit-matomo' );
    }

    public static function tearDownAfterClass(): void {
        self::$matomo->stop();
    }

    protected function setUp(): void {
        omsk_tests_reset_wp();
        self::$matomo->reset();
    }

    private function configure( array $overrides = array() ): void {
        $GLOBALS['omsk_wp']['options']['omsk-settings'] = array_merge(
            array(
                'omsk-matomo-host-field'                     => self::$matomo->url(),
                'omsk-matomo-idsite-field'                   => '3',
                'omsk-matomo-token-auth-field'               => 'secret-token',
                'omsk-matomo-enable-mtm-tracking-code-field' => 1,
                'omsk-matomo-idcontainer-field'              => 'AbC123',
            ),
            $overrides
        );
    }

    public function test_enabled_when_never_saved(): void {
        $this->assertTrue( omsk_is_ai_bot_tracking_enabled() );
        $this->assertTrue( omsk_is_ai_bot_tracking_enabled( array( 'omsk-matomo-host-field' => 'x' ) ) );
    }

    public function test_disabled_when_unchecked(): void {
        $this->assertFalse( omsk_is_ai_bot_tracking_enabled( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ) );
    }

    public function test_default_switches_legacy_sites_on_once(): void {
        $this->configure( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) );

        omsk_ai_bot_tracking_apply_default();

        $this->assertSame( 1, get_option( 'omsk-settings' )['omsk-matomo-enable-ai-bot-tracking-field'] );
        $this->assertSame( 1, get_option( 'omsk-ai-bot-tracking-default-applied' ) );

        // A later opt-out sticks.
        $settings = get_option( 'omsk-settings' );
        $settings['omsk-matomo-enable-ai-bot-tracking-field'] = 0;
        update_option( 'omsk-settings', $settings );

        omsk_ai_bot_tracking_apply_default();

        $this->assertSame( 0, get_option( 'omsk-settings' )['omsk-matomo-enable-ai-bot-tracking-field'] );
    }

    public function test_default_leaves_unconfigured_sites_untouched(): void {
        omsk_ai_bot_tracking_apply_default();

        $this->assertFalse( get_option( 'omsk-settings' ) );
        $this->assertSame( 1, get_option( 'omsk-ai-bot-tracking-default-applied' ) );
    }

    public function test_send_request_payload(): void {
        $this->configure();
        $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings' ) );

        $this->assertTrue( omsk_send_ai_bot_request( $config, self::CHATGPT, 'https://example.test/page/', 404, 87, 'Page' ) );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );

        $params = $hits[0]['params'];
        $this->assertSame( '3', $params['idsite'] );
        $this->assertSame( '1', $params['rec'] );
        $this->assertSame( '1', $params['recMode'] );
        $this->assertSame( 'WordPress', $params['source'] );
        $this->assertSame( '404', $params['http_status'] );
        $this->assertSame( '87', $params['pf_srv'] );
        $this->assertSame( 'https://example.test/page/', $params['url'] );
        $this->assertArrayNotHasKey( 'token_auth', $params );
        $this->assertArrayNotHasKey( 'cip', $params );
        $this->assertSame( self::CHATGPT, $hits[0]['ua'] );
    }

    public function test_send_request_runs_once_per_php_request(): void {
        $this->configure();
        $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings' ) );

        $this->assertTrue( omsk_send_ai_bot_request( $config, self::CHATGPT, 'https://example.test/' ) );
        $this->assertFalse( omsk_send_ai_bot_request( $config, self::CHATGPT, 'https://example.test/' ) );

        $this->assertCount( 1, self::$matomo->hits( 1 ) );
    }

    public function test_send_request_omits_unknown_server_time(): void {
        $this->configure();
        $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings' ) );

        omsk_send_ai_bot_request( $config, self::CHATGPT, 'https://example.test/' );

        $params = self::$matomo->hits( 1 )[0]['params'];
        $this->assertArrayNotHasKey( 'pf_srv', $params );
        $this->assertSame( '200', $params['http_status'] );
    }

    public function test_js_cookie_snippet_lists_user_agents(): void {
        $snippet = omsk_get_ai_bot_js_cookie_snippet();

        $this->assertStringStartsWith( '_paq.push([function () {', $snippet );
        $this->assertStringContainsString( '"ChatGPT-User"', $snippet );
        $this->assertStringContainsString( "'matomo_has_js=1;path='", $snippet );
        $this->assertStringContainsString( 'SameSite=Lax', $snippet );
    }

    public function test_dropin_snippet_is_relative_to_abspath_when_possible(): void {
        $snippet = omsk_get_ai_bot_dropin_snippet();
        $file    = wp_normalize_path( OMSK_TESTS_PLUGIN_DIR . 'modules/ai-bot-tracking/track.php' );

        // The tests run outside /var/www/html, so the absolute path is used.
        $this->assertSame( "if ( is_file( '{$file}' ) ) {\n\trequire_once '{$file}';\n}", $snippet );
    }

    public function test_cache_status_without_page_cache(): void {
        $status = omsk_get_ai_bot_cache_status();

        $this->assertFalse( $status['pageCache'] );
        $this->assertFalse( $status['dropinLoaded'] );
        $this->assertFalse( $status['liteSpeed'] );
        $this->assertStringContainsString( 'track.php', $status['dropinSnippet'] );
    }
}
