<?php
if (!defined('ABSPATH')) {
	exit;
}

final class OPC_Plugin {
	private static ?OPC_Plugin $instance = null;

	public static function instance(): OPC_Plugin {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		// Load translations (if present in /languages).
		load_plugin_textdomain(
			'optimal-page-counter',
			false,
			dirname(plugin_basename(OPC_PLUGIN_FILE)) . '/languages'
		);

		// Ensure settings are always available and forward-compatible.
		OPC_Utils::get_settings();

		if (is_admin()) {
			OPC_Admin::init();
		}
	}

	private function __construct() {}
	private function __clone() {}
}