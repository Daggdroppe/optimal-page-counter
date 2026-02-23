<?php
if (!defined('ABSPATH')) {
	exit;
}

final class OPC_DB {
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'opc_page_counter';
	}

	public static function activate(): void {
		self::create_table();
		self::maybe_add_default_options();
	}

	public static function deactivate(): void {
		// Milestone 3: remove cron events etc.
	}

	private static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// post_id as PK => 1 row per post, fastest path for lookups/updates.
		$sql = "CREATE TABLE {$table} (
			post_id BIGINT UNSIGNED NOT NULL,
			views BIGINT UNSIGNED NOT NULL DEFAULT 0,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (post_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function maybe_add_default_options(): void {
		if (get_option('opc_settings', null) !== null) {
			return;
		}
		add_option('opc_settings', OPC_Utils::default_settings(), '', false);
	}

	public static function drop_table(): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query("DROP TABLE IF EXISTS {$table}");
	}
}