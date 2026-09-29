<?php
/**
 * Front-end meta output and SEO plugin integration.
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end.
 */
class Ogmake_Frontend {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'wp_head', array( __CLASS__, 'print_meta' ), 5 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'filter_seo_image' ) );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'filter_seo_image' ) );
		add_filter( 'rank_math/opengraph/facebook/image', array( __CLASS__, 'filter_seo_image' ) );
		add_filter( 'rank_math/opengraph/twitter/image', array( __CLASS__, 'filter_seo_image' ) );
		add_action( 'save_post', array( __CLASS__, 'on_save' ) );
	}

	/**
	 * Is Yoast SEO or Rank Math active (they print their own og tags)?
	 *
	 * @return bool
	 */
	public static function seo_plugin_active() {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' );
	}

	/**
	 * URL for the current singular view, or empty when we should not act.
	 *
	 * @return string
	 */
	public static function current_url() {
		if ( ! is_singular() ) {
			return '';
		}
		$settings = Ogmake_Settings::get();
		$post_id  = get_queried_object_id();
		if ( ! in_array( get_post_type( $post_id ), (array) $settings['post_types'], true ) ) {
			return '';
		}
		if ( ! empty( $settings['use_featured'] ) && has_post_thumbnail( $post_id ) ) {
			return '';
		}
		return Ogmake_Url::for_post( $post_id );
	}

	/**
	 * Print og:image / twitter:image when no SEO plugin is doing it.
	 *
	 * @return void
	 */
	public static function print_meta() {
		if ( self::seo_plugin_active() ) {
			return;
		}
		$url = self::current_url();
		if ( '' === $url ) {
			return;
		}
		echo '<meta property="og:image" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:image:width" content="1200" />' . "\n";
		echo '<meta property="og:image:height" content="630" />' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $url ) . '" />' . "\n";
	}

	/**
	 * Yoast / Rank Math image filter: keep theirs unless the user opted to
	 * override (or they have none).
	 *
	 * @param mixed $image Existing image URL.
	 * @return mixed
	 */
	public static function filter_seo_image( $image ) {
		$settings = Ogmake_Settings::get();
		if ( ! empty( $image ) && empty( $settings['override_seo'] ) ) {
			return $image;
		}
		$url = self::current_url();
		return '' !== $url ? $url : $image;
	}

	/**
	 * Drop the post's cached URL on save is unnecessary (the cache key embeds
	 * the params), but stale keys expire by TTL. Nothing to purge; hook kept
	 * for the meta box save to share.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function on_save( $post_id ) {
		unset( $post_id );
	}
}
