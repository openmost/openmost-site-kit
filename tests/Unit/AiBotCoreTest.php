<?php
/**
 * @package Openmost_Site_Kit
 */

namespace Openmost\SiteKit\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AiBotCoreTest extends TestCase {

    protected function setUp(): void {
        omsk_tests_reset_wp();
    }

    public static function chatbotUserAgents(): array {
        return array(
            'ChatGPT'    => array( 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot' ),
            'Claude'     => array( 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)' ),
            'Perplexity' => array( 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)' ),
            'Mistral'    => array( 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; MistralAI-User/1.0; +https://docs.mistral.ai/robots)' ),
            'NotebookLM' => array( 'Google-NotebookLM' ),
            'lowercase'  => array( 'chatgpt-user/1.0' ),
        );
    }

    #[DataProvider( 'chatbotUserAgents' )]
    public function test_detects_ai_chatbots( string $user_agent ): void {
        $this->assertTrue( omsk_is_ai_bot_user_agent( $user_agent ) );
    }

    public static function otherUserAgents(): array {
        return array(
            'Chrome'    => array( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36' ),
            'Googlebot' => array( 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' ),
            'empty'     => array( '' ),
        );
    }

    #[DataProvider( 'otherUserAgents' )]
    public function test_ignores_humans_and_search_crawlers( string $user_agent ): void {
        $this->assertFalse( omsk_is_ai_bot_user_agent( $user_agent ) );
    }

    public function test_user_agent_list_is_filterable(): void {
        $GLOBALS['omsk_wp']['filters']['omsk_ai_bot_user_agents'] = static fn( array $list ) => array_merge( $list, array( 'AcmeAssistant' ) );

        $this->assertTrue( omsk_is_ai_bot_user_agent( 'Mozilla/5.0 AcmeAssistant/2.0' ) );
    }

    public function test_request_user_agent_strips_control_characters(): void {
        $_SERVER['HTTP_USER_AGENT'] = " ChatGPT-User/1.0\r\nX-Injected: 1 ";

        $this->assertSame( 'ChatGPT-User/1.0X-Injected: 1', omsk_get_request_user_agent() );
    }

    public function test_js_cookie_only_counts_when_set_to_one(): void {
        $this->assertFalse( omsk_ai_bot_has_js_cookie() );

        $_COOKIE['matomo_has_js'] = '0';
        $this->assertFalse( omsk_ai_bot_has_js_cookie() );

        $_COOKIE['matomo_has_js'] = '1';
        $this->assertTrue( omsk_ai_bot_has_js_cookie() );
    }

    private static function options( array $overrides = array() ): array {
        return array_merge(
            array(
                'omsk-matomo-host-field'                  => 'https://matomo.example.test',
                'omsk-matomo-idsite-field'                => '7',
                'omsk-matomo-enable-mtm-tracking-code-field' => 1,
                'omsk-matomo-idcontainer-field'           => 'AbC123',
            ),
            $overrides
        );
    }

    public function test_config_is_on_by_default(): void {
        $this->assertSame(
            array(
                'host'    => 'https://matomo.example.test',
                'id_site' => 7,
                'esi'     => false,
            ),
            omsk_get_ai_bot_tracking_config( self::options() )
        );
    }

    public function test_config_respects_opt_out(): void {
        $this->assertNull( omsk_get_ai_bot_tracking_config( self::options( array( 'omsk-matomo-enable-ai-bot-tracking-field' => 0 ) ) ) );
    }

    public static function trackingMethods(): array {
        return array(
            'classic'           => array( array( 'omsk-matomo-enable-classic-tracking-code-field' => 1 ), true ),
            'tag manager'       => array( array( 'omsk-matomo-enable-mtm-tracking-code-field' => 1, 'omsk-matomo-idcontainer-field' => 'x' ), true ),
            'server'            => array( array( 'omsk-matomo-enable-server-tracking-field' => 1 ), true ),
            'tag manager no id' => array( array( 'omsk-matomo-enable-mtm-tracking-code-field' => 1 ), false ),
            'none'              => array( array(), false ),
        );
    }

    #[DataProvider( 'trackingMethods' )]
    public function test_config_runs_with_every_tracking_method( array $method, bool $expected ): void {
        $options = array_merge(
            array(
                'omsk-matomo-host-field'   => 'https://matomo.example.test',
                'omsk-matomo-idsite-field' => '7',
            ),
            $method
        );

        $this->assertSame( $expected, null !== omsk_get_ai_bot_tracking_config( $options ) );
    }

    public function test_config_requires_host_and_site(): void {
        $this->assertNull( omsk_get_ai_bot_tracking_config( self::options( array( 'omsk-matomo-host-field' => '' ) ) ) );
        $this->assertNull( omsk_get_ai_bot_tracking_config( self::options( array( 'omsk-matomo-idsite-field' => '' ) ) ) );
        $this->assertNull( omsk_get_ai_bot_tracking_config( array() ) );
        $this->assertNull( omsk_get_ai_bot_tracking_config( false ) );
    }

    public function test_config_exposes_esi(): void {
        $this->assertTrue( omsk_get_ai_bot_tracking_config( self::options( array( 'omsk-matomo-ai-bot-esi-field' => 1 ) ) )['esi'] );
    }

    public function test_current_url_honours_https_and_proxies(): void {
        $_SERVER['HTTP_HOST']   = 'www.example.test';
        $_SERVER['REQUEST_URI'] = '/blog/?p=1';
        unset( $_SERVER['HTTPS'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_X_FORWARDED_PROTO'] );

        $this->assertSame( 'http://www.example.test/blog/?p=1', omsk_ai_bot_current_url() );

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertSame( 'https://www.example.test/blog/?p=1', omsk_ai_bot_current_url() );

        unset( $_SERVER['HTTP_X_FORWARDED_PROTO'] );
        $_SERVER['HTTPS'] = 'on';
        $this->assertSame( 'https://www.example.test/blog/?p=1', omsk_ai_bot_current_url() );

        unset( $_SERVER['HTTPS'] );
    }

    public function test_current_url_drops_invalid_host_characters(): void {
        $_SERVER['HTTP_HOST']   = 'evil.test/<script>';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertSame( 'http://evil.testscript/', omsk_ai_bot_current_url() );
    }

    public static function esiUrls(): array {
        return array(
            'same host'          => array( 'https://www.example.test/blog/post/', true ),
            'same host http'     => array( 'http://WWW.example.test/', true ),
            'other host'         => array( 'https://attacker.test/', false ),
            'javascript scheme'  => array( 'javascript://www.example.test/%0aalert(1)', false ),
            'relative'           => array( '/blog/', false ),
            'empty'              => array( '', false ),
        );
    }

    #[DataProvider( 'esiUrls' )]
    public function test_esi_only_accepts_own_urls( string $url, bool $expected ): void {
        $this->assertSame( $expected, omsk_ai_bot_is_own_url( $url, 'https://www.example.test' ) );
    }
}
