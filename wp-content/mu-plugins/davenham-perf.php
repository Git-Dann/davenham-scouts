<?php
/**
 * Plugin Name: Davenham Performance
 * Description: Lightweight must-use performance tweaks for the Davenham Scouts site. Auto-loads (mu-plugin) — no activation needed.
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slow the WordPress Heartbeat API.
 *
 * In the admin the Heartbeat fires every 15 seconds, and on this shared
 * LiteSpeed host each admin-ajax heartbeat request costs ~0.7s of server
 * time — so simply leaving an admin tab open hammers the server all day.
 * Raising the interval to 60s keeps autosave and post-locking working while
 * cutting that background load roughly 4x. Closures are PHP 7.0-safe
 * (arrow functions are deliberately avoided for 7.0 compatibility).
 */
add_filter(
	'heartbeat_settings',
	function ( $settings ) {
		$settings['interval'] = 60; // seconds; default is 15 in wp-admin.
		return $settings;
	}
);
