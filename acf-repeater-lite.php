<?php
/**
 * Plugin Name: ACF Repeater Lite
 * Description: Adds a lightweight repeater-style custom field type to Advanced Custom Fields (Free/Pro) without requiring ACF Pro's Repeater field.
 * Version: 1.0.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: acf-repeater-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACF_Repeater_Lite_Plugin {
	const VERSION = '1.0.1';

	public static function init() {
		add_action( 'acf/include_field_types', array( __CLASS__, 'register_field_type' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_acf_notice' ) );
	}

	public static function register_field_type() {
		if ( ! class_exists( 'acf_field' ) ) {
			return;
		}

		require_once __DIR__ . '/includes/class-acf-field-repeater-lite.php';
		new ACF_Field_Repeater_Lite();
	}

	public static function maybe_show_acf_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( class_exists( 'ACF' ) || function_exists( 'acf' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'ACF Repeater Lite requires Advanced Custom Fields to be installed and active.', 'acf-repeater-lite' );
		echo '</p></div>';
	}
}


ACF_Repeater_Lite_Plugin::init();
