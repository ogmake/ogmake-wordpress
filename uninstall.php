<?php
/**
 * Remove all plugin data on uninstall.
 *
 * @package Ogmake
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'ogmake_settings' );
delete_option( 'ogmake_cache_gen' );

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_ogmake_disable','_ogmake_template','_ogmake_title')" );
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_ogmake_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_ogmake_' ) . '%'
	)
);
// phpcs:enable
