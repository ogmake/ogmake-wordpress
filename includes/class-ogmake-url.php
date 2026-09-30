<?php
/**
 * Builds ogmake image URLs for posts.
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL builder.
 */
class Ogmake_Url {

	/**
	 * Strip tags, collapse whitespace, truncate on a character boundary.
	 *
	 * @param string $text Text.
	 * @param int    $max  Max characters.
	 * @return string
	 */
	public static function clip( $text, $max ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
		if ( function_exists( 'mb_substr' ) && mb_strlen( $text ) > $max ) {
			return rtrim( mb_substr( $text, 0, $max - 1 ) ) . "\xE2\x80\xA6";
		}
		return $text;
	}

	/**
	 * Query params for a post. Pure: takes plain values.
	 *
	 * @param array<string,string> $fields   title, description, author, date.
	 * @param array<string,mixed>  $settings Plugin settings.
	 * @param string               $site     Site name.
	 * @return array<string,string>
	 */
	public static function params( array $fields, array $settings, $site ) {
		$params = array(
			'title' => self::clip( $fields['title'] ?? '', 120 ),
			'site'  => self::clip( '' !== (string) $settings['site_name'] ? $settings['site_name'] : $site, 60 ),
		);
		$desc   = self::clip( $fields['description'] ?? '', 200 );
		if ( '' !== $desc ) {
			$params['description'] = $desc;
		}
		if ( ! empty( $fields['author'] ) ) {
			$params['author'] = self::clip( $fields['author'], 60 );
		}
		if ( ! empty( $fields['date'] ) ) {
			$params['date'] = self::clip( $fields['date'], 40 );
		}
		if ( ! empty( $settings['background'] ) ) {
			$params['bg'] = (string) $settings['background'];
		}
		return $params;
	}

	/**
	 * Final URL for a template + params, per the configured mode.
	 *
	 * @param string               $template Template id.
	 * @param array<string,string> $params   Params.
	 * @param array<string,mixed>  $settings Plugin settings.
	 * @return string Empty string when not configured.
	 */
	public static function build( $template, array $params, array $settings ) {
		$key_id = (string) $settings['key_id'];
		$base   = rtrim( (string) $settings['base_url'], '/' );
		if ( '' === $key_id || '' === $base || '' === $template ) {
			return '';
		}
		if ( 'signed' === $settings['mode'] ) {
			$url = Ogmake_Signing::signed_url( $base, $key_id, (string) $settings['signing_secret'], array_merge( array( 'template' => $template ), $params ) );
			return null === $url ? '' : $url;
		}
		return $base . '/p/' . rawurlencode( $key_id ) . '/' . rawurlencode( $template ) . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Image URL for a post, cached in a transient. Empty when the post is
	 * opted out or the plugin is not configured.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function for_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		if ( get_post_meta( $post->ID, '_ogmake_disable', true ) ) {
			return '';
		}
		$settings = Ogmake_Settings::get();
		$template = (string) get_post_meta( $post->ID, '_ogmake_template', true );
		$template = '' !== $template ? $template : (string) $settings['template'];
		$title    = (string) get_post_meta( $post->ID, '_ogmake_title', true );
		// Password-protected post: the og image URL is public and cached, so never put the
		// body or excerpt in it (title only). Check the stored password, NOT
		// post_password_required(), which is false for a visitor who already unlocked it.
		$protected = '' !== (string) ( $post->post_password ?? '' );
		if ( $protected ) {
			$description   = '';
			$default_title = (string) $post->post_title;
		} else {
			$description   = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( $post->post_content, 30, '' );
			$default_title = get_the_title( $post );
		}
		$fields = array(
			'title'       => '' !== $title ? $title : $default_title,
			'description' => $description,
			'author'      => get_the_author_meta( 'display_name', (int) $post->post_author ),
			'date'        => get_the_date( '', $post ),
		);
		$params = self::params( $fields, $settings, get_bloginfo( 'name' ) );

		$key    = 'ogmake_' . md5( wp_json_encode( array( get_option( 'ogmake_cache_gen', 0 ), $post->ID, $template, $params, $settings['mode'], $settings['key_id'], $settings['base_url'] ) ) );
		$cached = get_transient( $key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}
		$url = self::build( $template, $params, $settings );
		if ( '' !== $url ) {
			set_transient( $key, $url, DAY_IN_SECONDS );
		}
		return $url;
	}
}
