<?php
/**
 * End-to-end AI chatbot tracking on a real WordPress site.
 *
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Integration;

use Openmost\SiteKit\Tests\Support\FakeMatomo;
use Openmost\SiteKit\Tests\Support\WordPressSite;
use PHPUnit\Framework\TestCase;

final class AiBotTrackingTest extends TestCase {

    private const CHATGPT = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot';
    private const CLAUDE  = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)';
    private const HUMAN   = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private static FakeMatomo $matomo;

    private static WordPressSite $site;

    public static function setUpBeforeClass(): void {
        self::$matomo = new FakeMatomo( OMSK_TESTS_TMP_DIR . '/integration-matomo' );
        self::$site   = new WordPressSite( OMSK_TESTS_TMP_DIR . '/site' );
    }

    public static function tearDownAfterClass(): void {
        self::$site->stop();
        self::$matomo->stop();
    }

    protected function setUp(): void {
        self::$site->set_page_cache( false );
        self::$site->set_dropin( false );
        self::$site->set_state(
            array(
                'settings'       => self::settings(),
                'defaultApplied' => true,
                'active'         => true,
            )
        );
        self::$matomo->reset();
    }

    private static function settings( array $overrides = array() ): array {
        return array_merge(
            array(
                'omsk-matomo-host-field'                     => self::$matomo->url(),
                'omsk-matomo-idsite-field'                   => '5',
                'omsk-matomo-token-auth-field'               => 'secret-token',
                'omsk-matomo-enable-mtm-tracking-code-field' => 1,
                'omsk-matomo-idcontainer-field'              => 'AbC123',
            ),
            $overrides
        );
    }

    private static function classic(): array {
        return array(
            'omsk-matomo-enable-mtm-tracking-code-field'     => 0,
            'omsk-matomo-enable-classic-tracking-code-field' => 1,
        );
    }

    public function test_tag_manager_site_tracks_chatbot_server_side(): void {
        $response = self::$site->get( '/', self::CHATGPT );

        $this->assertSame( 200, $response['status'] );
        $this->assertStringContainsString( 'container_AbC123.js', $response['body'] );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );

        $params = $hits[0]['params'];
        $this->assertSame( '5', $params['idsite'] );
        $this->assertSame( '1', $params['recMode'] );
        $this->assertSame( 'WordPress', $params['source'] );
        $this->assertSame( '200', $params['http_status'] );
        $this->assertSame( self::$site->url( '/' ), $params['url'] );
        $this->assertArrayHasKey( 'pf_srv', $params );
        $this->assertGreaterThan( 0, (int) $params['pf_srv'] );
        $this->assertArrayNotHasKey( 'token_auth', $params );
        $this->assertSame( self::CHATGPT, $hits[0]['ua'] );
    }

    public function test_classic_site_tracks_chatbot_and_flags_js_agents(): void {
        self::$site->set_state( array( 'settings' => self::settings( self::classic() ) ) );

        $response = self::$site->get( '/', self::CLAUDE );

        $this->assertStringContainsString( "matomo.php?recMode=2&source=WordPress", $response['body'] );
        $this->assertStringContainsString( 'matomo_has_js=1', $response['body'] );
        $this->assertStringContainsString( '"Claude-User"', $response['body'] );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );
        $this->assertSame( self::CLAUDE, $hits[0]['ua'] );
    }

    public function test_human_visitor_sends_nothing_server_side(): void {
        self::$site->get( '/', self::HUMAN );

        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_chatbot_that_ran_javascript_is_not_counted_twice(): void {
        self::$site->get( '/', self::CHATGPT, array( 'matomo_has_js' => '1' ) );

        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_not_found_page_reports_its_status(): void {
        $response = self::$site->get( '/?p=999999', self::CHATGPT );

        $this->assertSame( 404, $response['status'] );
        $this->assertSame( '404', self::$matomo->hits( 1 )[0]['params']['http_status'] );
    }

    public function test_opt_out_stops_tracking(): void {
        self::$site->set_state( array( 'settings' => self::settings( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ) ) );

        $response = self::$site->get( '/', self::CHATGPT );

        $this->assertSame( array(), self::$matomo->hits() );
        $this->assertStringNotContainsString( 'esi:include', $response['body'] );
    }

    public function test_classic_opt_out_removes_client_side_bot_mode(): void {
        self::$site->set_state( array( 'settings' => self::settings( array_merge( self::classic(), array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ) ) ) );

        $response = self::$site->get( '/', self::HUMAN );

        $this->assertStringNotContainsString( 'recMode', $response['body'] );
        $this->assertStringNotContainsString( 'matomo_has_js', $response['body'] );
    }

    public function test_sites_saved_before_the_feature_existed_are_switched_on_once(): void {
        self::$site->set_state(
            array(
                'settings'       => self::settings( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ),
                'defaultApplied' => false,
            )
        );

        self::$site->get( '/', self::CHATGPT );

        $this->assertCount( 1, self::$matomo->hits( 1 ) );
        $this->assertSame( 1, self::$site->option( 'omsk-settings' )['omsk-matomo-enable-ai-bot-tracking-field'] );
        $this->assertSame( 1, (int) self::$site->option( 'omsk-ai-bot-tracking-default-applied' ) );
    }

    public function test_nothing_is_sent_without_a_tracking_method(): void {
        self::$site->set_state(
            array(
                'settings' => self::settings(
                    array(
                        'omsk-matomo-enable-mtm-tracking-code-field' => 0,
                        'omsk-matomo-idcontainer-field'              => '',
                    )
                ),
            )
        );

        self::$site->get( '/', self::CHATGPT );

        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_server_side_tracking_never_counts_chatbots_as_visits(): void {
        self::$site->set_state(
            array(
                'settings' => self::settings(
                    array(
                        'omsk-matomo-enable-mtm-tracking-code-field' => 0,
                        'omsk-matomo-enable-server-tracking-field'   => 1,
                    )
                ),
            )
        );

        // Claude-User contains no generic bot keyword, only the AI list catches it.
        self::$site->get( '/', self::CLAUDE );

        $hits = self::$matomo->hits( 2, 1.5 );
        $this->assertCount( 1, $hits );
        $this->assertSame( '1', $hits[0]['params']['recMode'] );

        self::$matomo->reset();
        self::$site->get( '/', self::HUMAN );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );
        $this->assertArrayNotHasKey( 'recMode', $hits[0]['params'] );
    }

    public function test_cached_page_is_missed_without_the_dropin(): void {
        self::$site->set_page_cache( true, '<html><body>cached</body></html>' );

        $response = self::$site->get( '/', self::CHATGPT );

        $this->assertStringContainsString( 'X-Test-Cache: hit', $response['headers'] );
        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_cached_page_is_tracked_by_the_dropin(): void {
        self::$site->set_page_cache( true, '<html><body>cached</body></html>' );
        self::$site->set_dropin( true );

        $response = self::$site->get( '/?p=1', self::CHATGPT );

        $this->assertStringContainsString( 'X-Test-Cache: hit', $response['headers'] );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );
        $this->assertSame( '1', $hits[0]['params']['recMode'] );
        $this->assertSame( '200', $hits[0]['params']['http_status'] );
        $this->assertSame( self::$site->url( '/?p=1' ), $hits[0]['params']['url'] );
        $this->assertArrayNotHasKey( 'token_auth', $hits[0]['params'] );
    }

    public function test_dropin_ignores_humans_and_js_agents_on_cached_pages(): void {
        self::$site->set_page_cache( true, '<html><body>cached</body></html>' );
        self::$site->set_dropin( true );

        self::$site->get( '/', self::HUMAN );
        self::$site->get( '/', self::CHATGPT, array( 'matomo_has_js' => '1' ) );

        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_dropin_respects_opt_out_and_deactivation(): void {
        self::$site->set_page_cache( true, '<html><body>cached</body></html>' );
        self::$site->set_dropin( true );

        self::$site->set_state( array( 'settings' => self::settings( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ) ) );
        self::$site->get( '/', self::CHATGPT );
        $this->assertSame( array(), self::$matomo->hits() );

        self::$site->set_state(
            array(
                'settings' => self::settings(),
                'active'   => false,
            )
        );
        self::$site->get( '/', self::CHATGPT );
        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_dropin_does_not_double_count_cache_misses(): void {
        self::$site->set_page_cache( true );
        self::$site->set_dropin( true );

        $response = self::$site->get( '/', self::CHATGPT );

        $this->assertStringNotContainsString( 'X-Test-Cache: hit', $response['headers'] );
        $this->assertCount( 1, self::$matomo->hits( 2, 1.5 ) );
    }

    public function test_settings_report_cache_and_dropin_state(): void {
        $status = self::$site->cache_status();
        $this->assertFalse( $status['pageCache'] );
        $this->assertFalse( $status['dropinLoaded'] );
        $this->assertStringContainsString( 'modules/ai-bot-tracking/track.php', $status['dropinSnippet'] );

        self::$site->set_page_cache( true );
        self::$site->set_dropin( true );

        $status = self::$site->cache_status();
        $this->assertTrue( $status['pageCache'] );
        $this->assertTrue( $status['dropinLoaded'] );
    }

    public function test_esi_mode_prints_the_include_instead_of_tracking(): void {
        self::$site->set_state( array( 'settings' => self::settings( array( 'omsk-matomo-ai-bot-esi-field' => 1 ) ) ) );

        $response = self::$site->get( '/?p=1', self::CHATGPT );

        $this->assertMatchesRegularExpression( '#<esi:include src="[^"]+/modules/ai-bot-tracking/track\.php\?omsk_esi=[^"]+" cache-control="no-cache" />#', $response['body'] );
        $this->assertStringContainsString( rawurlencode( self::$site->url( '/?p=1' ) ), $response['body'] );
        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_esi_include_tracks_the_chatbot(): void {
        self::$site->set_state( array( 'settings' => self::settings( array( 'omsk-matomo-ai-bot-esi-field' => 1 ) ) ) );

        $page     = self::$site->url( '/?p=1' );
        $response = self::$site->get( self::esi_path( $page ), self::CHATGPT );

        $this->assertSame( 200, $response['status'] );
        $this->assertSame( '', trim( $response['body'] ) );
        $this->assertStringContainsStringIgnoringCase( 'Cache-Control: no-store', $response['headers'] );

        $hits = self::$matomo->hits( 1 );
        $this->assertCount( 1, $hits );
        $this->assertSame( $page, $hits[0]['params']['url'] );
        $this->assertSame( '1', $hits[0]['params']['recMode'] );
        $this->assertSame( self::CHATGPT, $hits[0]['ua'] );
    }

    public function test_esi_endpoint_rejects_foreign_urls_humans_and_disabled_sites(): void {
        self::$site->set_state( array( 'settings' => self::settings( array( 'omsk-matomo-ai-bot-esi-field' => 1 ) ) ) );

        self::$site->get( self::esi_path( 'https://attacker.test/' ), self::CHATGPT );
        self::$site->get( self::esi_path( self::$site->url( '/' ) ), self::HUMAN );
        $this->assertSame( array(), self::$matomo->hits() );

        self::$site->set_state( array( 'settings' => self::settings() ) );
        self::$site->get( self::esi_path( self::$site->url( '/' ) ), self::CHATGPT );
        $this->assertSame( array(), self::$matomo->hits() );
    }

    public function test_esi_endpoint_is_inert_without_parameter(): void {
        $response = self::$site->get( '/wp-content/plugins/openmost-site-kit/modules/ai-bot-tracking/track.php', self::CHATGPT );

        $this->assertSame( '', trim( $response['body'] ) );
        $this->assertSame( array(), self::$matomo->hits() );
    }

    private static function esi_path( string $page_url ): string {
        return '/wp-content/plugins/openmost-site-kit/modules/ai-bot-tracking/track.php?omsk_esi=' . rawurlencode( $page_url );
    }
}
