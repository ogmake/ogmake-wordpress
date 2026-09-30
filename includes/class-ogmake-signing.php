<?php
/**
 * HMAC signing, a port of packages/core/src/signing.ts (same algorithm as the
 * Laravel package): drop `sig`/`debug`, sort keys by UTF-16 code unit,
 * RFC3986-encode, HMAC-SHA256, base64url.
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Signing helpers.
 */
class Ogmake_Signing {

	/**
	 * Canonical query string for signing.
	 *
	 * @param array<string,string> $params Query params.
	 * @return string
	 */
	public static function canonical_query( array $params ) {
		unset( $params['sig'], $params['debug'] );
		uksort( $params, array( __CLASS__, 'compare_utf16' ) );
		$parts = array();
		foreach ( $params as $key => $value ) {
			$parts[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
		}
		return implode( '&', $parts );
	}

	/**
	 * Compare two strings by UTF-16 code unit (JS default sort order).
	 *
	 * @param string $a First.
	 * @param string $b Second.
	 * @return int
	 */
	public static function compare_utf16( $a, $b ) {
		$ua  = self::utf16_units( (string) $a );
		$ub  = self::utf16_units( (string) $b );
		$len = min( count( $ua ), count( $ub ) );
		for ( $i = 0; $i < $len; $i++ ) {
			if ( $ua[ $i ] !== $ub[ $i ] ) {
				return $ua[ $i ] <=> $ub[ $i ];
			}
		}
		return count( $ua ) <=> count( $ub );
	}

	/**
	 * UTF-8 string to UTF-16 code units.
	 *
	 * @param string $value UTF-8 string.
	 * @return int[]
	 */
	private static function utf16_units( $value ) {
		$units = array();
		$len   = strlen( $value );
		$i     = 0;
		while ( $i < $len ) {
			$byte = ord( $value[ $i ] );
			if ( $byte < 0x80 ) {
				$cp = $byte;
				++$i;
			} elseif ( ( $byte & 0xE0 ) === 0xC0 && $i + 1 < $len ) {
				$cp = ( ( $byte & 0x1F ) << 6 ) | ( ord( $value[ $i + 1 ] ) & 0x3F );
				$i += 2;
			} elseif ( ( $byte & 0xF0 ) === 0xE0 && $i + 2 < $len ) {
				$cp = ( ( $byte & 0x0F ) << 12 ) | ( ( ord( $value[ $i + 1 ] ) & 0x3F ) << 6 ) | ( ord( $value[ $i + 2 ] ) & 0x3F );
				$i += 3;
			} elseif ( ( $byte & 0xF8 ) === 0xF0 && $i + 3 < $len ) {
				$cp = ( ( $byte & 0x07 ) << 18 ) | ( ( ord( $value[ $i + 1 ] ) & 0x3F ) << 12 ) | ( ( ord( $value[ $i + 2 ] ) & 0x3F ) << 6 ) | ( ord( $value[ $i + 3 ] ) & 0x3F );
				$i += 4;
			} else {
				$cp = $byte;
				++$i;
			}
			if ( $cp > 0xFFFF ) {
				$cp     -= 0x10000;
				$units[] = 0xD800 + ( $cp >> 10 );
				$units[] = 0xDC00 + ( $cp & 0x3FF );
			} else {
				$units[] = $cp;
			}
		}
		return $units;
	}

	/**
	 * Sign a canonical query with a base64 secret.
	 *
	 * @param string $secret_base64 Base64 signing secret.
	 * @param string $canonical     Canonical query.
	 * @return string|null base64url signature, or null for an invalid secret.
	 */
	public static function sign( $secret_base64, $canonical ) {
		$secret = base64_decode( $secret_base64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $secret || '' === $secret ) {
			return null;
		}
		$mac = hash_hmac( 'sha256', $canonical, $secret, true );
		return rtrim( strtr( base64_encode( $mac ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Full signed URL: {base}/i/{keyId}/{sig}?canonical.
	 *
	 * @param string               $base_url Base URL.
	 * @param string               $key_id   Key id.
	 * @param string               $secret   Base64 secret.
	 * @param array<string,string> $params   Params.
	 * @return string|null
	 */
	public static function signed_url( $base_url, $key_id, $secret, array $params ) {
		$canonical = self::canonical_query( $params );
		$sig       = self::sign( $secret, $canonical );
		if ( null === $sig ) {
			return null;
		}
		return rtrim( $base_url, '/' ) . '/i/' . rawurlencode( $key_id ) . '/' . $sig . '?' . $canonical;
	}
}
