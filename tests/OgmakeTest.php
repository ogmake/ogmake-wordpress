<?php
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class OgmakeTest extends TestCase {

	const SECRET = 'AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8=';

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubs(
			array(
				'wp_strip_all_tags' => function ( $s ) {
					return strip_tags( $s );
				},
				'esc_url'           => function ( $s ) {
					return $s;
				},
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function settings( array $over = array() ) {
		return array_merge( Ogmake_Settings::defaults(), array( 'key_id' => 'k_abc' ), $over );
	}

	public function test_signing_matches_shared_vector() {
		$canonical = Ogmake_Signing::canonical_query( array( 'template' => 'basic', 'title' => 'Hello World', 'format' => 'png', 'sig' => 'x', 'debug' => '1' ) );
		$this->assertSame( 'format=png&template=basic&title=Hello%20World', $canonical );
		$this->assertSame( 'W8Xvrf9UTFKbVVQtofHq7A2ljK9z-nvJUL3B-rB7MY8', Ogmake_Signing::sign( self::SECRET, $canonical ) );
	}

	public function test_utf16_sort_order_astral_before_private_use() {
		$canonical = Ogmake_Signing::canonical_query( array( "\xEE\x80\x80" => '1', "\xF0\x9F\x98\x80" => '2' ) );
		$this->assertStringStartsWith( '%F0%9F%98%80=2', $canonical );
	}

	public function test_invalid_secret_yields_no_url() {
		$this->assertNull( Ogmake_Signing::signed_url( 'https://ogmake.com', 'k_abc', '!!!', array( 'title' => 'x' ) ) );
		$this->assertSame( '', Ogmake_Url::build( 'basic', array( 'title' => 'x' ), $this->settings( array( 'mode' => 'signed', 'signing_secret' => '!!!' ) ) ) );
	}

	public function test_public_url_shape() {
		$url = Ogmake_Url::build( 'blog', array( 'title' => 'Hello World', 'site' => 'My Blog' ), $this->settings() );
		$this->assertSame( 'https://ogmake.com/p/k_abc/blog?title=Hello%20World&site=My%20Blog', $url );
	}

	public function test_signed_url_has_sig_and_no_secret() {
		$url = Ogmake_Url::build( 'basic', array( 'title' => 'Hi' ), $this->settings( array( 'mode' => 'signed', 'signing_secret' => self::SECRET ) ) );
		$this->assertMatchesRegularExpression( '#^https://ogmake\.com/i/k_abc/[A-Za-z0-9_-]{43}\?template=basic&title=Hi$#', $url );
		$this->assertStringNotContainsString( self::SECRET, $url );
	}

	public function test_unconfigured_returns_empty() {
		$this->assertSame( '', Ogmake_Url::build( 'basic', array(), Ogmake_Settings::defaults() ) );
	}

	public function test_params_clip_and_optional_fields() {
		$p = Ogmake_Url::params( array( 'title' => str_repeat( 'a', 300 ), 'description' => '<b>Hi</b>  there', 'author' => 'Jane' ), $this->settings( array( 'background' => 'bg-01' ) ), 'Site' );
		$this->assertSame( 120, mb_strlen( $p['title'] ) );
		$this->assertSame( 'Hi there', $p['description'] );
		$this->assertSame( 'Jane', $p['author'] );
		$this->assertSame( 'bg-01', $p['bg'] );
		$this->assertSame( 'Site', $p['site'] );
		$this->assertArrayNotHasKey( 'date', $p );
	}

	public function test_seo_filter_keeps_existing_unless_override() {
		Functions\when( 'get_option' )->justReturn( array() );
		$this->assertSame( 'https://x/y.png', Ogmake_Frontend::filter_seo_image( 'https://x/y.png' ) );
	}

	public function test_seo_filter_overrides_when_opted_in() {
		Functions\when( 'get_option' )->justReturn( array( 'override_seo' => 1, 'key_id' => 'k_abc', 'post_types' => array( 'post' ) ) );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 5 );
		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'get_post' )->justReturn( (object) array( 'ID' => 5, 'post_content' => 'body', 'post_author' => 1 ) );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'T' );
		Functions\when( 'has_excerpt' )->justReturn( false );
		Functions\when( 'wp_trim_words' )->justReturn( 'body' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Jane' );
		Functions\when( 'get_the_date' )->justReturn( 'Sep 1, 2026' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Blog' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )->once();
		$out = Ogmake_Frontend::filter_seo_image( 'https://x/y.png' );
		$this->assertStringStartsWith( 'https://ogmake.com/p/k_abc/basic?title=T', $out );
	}

	public function test_opted_out_post_gets_no_url() {
		Functions\when( 'get_post' )->justReturn( (object) array( 'ID' => 9 ) );
		Functions\when( 'get_post_meta' )->justReturn( '1' );
		$this->assertSame( '', Ogmake_Url::for_post( 9 ) );
	}

	public function test_featured_image_wins_when_enabled() {
		Functions\when( 'get_option' )->justReturn( array( 'use_featured' => 1, 'post_types' => array( 'post' ) ) );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 5 );
		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'has_post_thumbnail' )->justReturn( true );
		$this->assertSame( '', Ogmake_Frontend::current_url() );
	}

	private function stub_for_post( $post ) {
		Functions\when( 'get_option' )->justReturn( array( 'key_id' => 'k_abc' ) );
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'Protected: Secret plan' );
		Functions\when( 'has_excerpt' )->justReturn( true );
		Functions\when( 'get_the_excerpt' )->justReturn( 'There is no excerpt because this is a protected post.' );
		Functions\when( 'wp_trim_words' )->justReturn( 'SECRET BODY TEXT' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Jane' );
		Functions\when( 'get_the_date' )->justReturn( 'Sep 1, 2026' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Blog' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
	}

	public function test_password_protected_post_uses_title_only() {
		$this->stub_for_post( (object) array( 'ID' => 7, 'post_title' => 'Secret plan', 'post_content' => 'SECRET BODY TEXT', 'post_excerpt' => 'SECRET EXCERPT', 'post_password' => 'hunter2', 'post_author' => 1 ) );
		$url = Ogmake_Url::for_post( 7 );
		$this->assertStringContainsString( 'title=Secret%20plan', $url );
		$this->assertStringNotContainsString( 'description=', $url );
		$this->assertStringNotContainsString( 'SECRET', $url );
		$this->assertStringNotContainsString( 'protected', $url );
	}

	public function test_unprotected_post_keeps_description() {
		$this->stub_for_post( (object) array( 'ID' => 8, 'post_title' => 'Open', 'post_content' => 'body', 'post_password' => '', 'post_author' => 1 ) );
		$this->assertStringContainsString( 'description=', Ogmake_Url::for_post( 8 ) );
	}
}
