<?php
/**
 * Plugin Name: Optimal Page Counter
 * Description: A lightweight, cache-first WordPress page counter focused on performance, privacy, and minimal database impact.
 * Version: 0.1.0
 * Author: Rasmus Nertlinge
 * Text Domain: optimal-page-counter
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
	exit;
}

define('OPC_VERSION', '0.1.0');
define('OPC_PLUGIN_FILE', __FILE__);
define('OPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once OPC_PLUGIN_DIR . 'includes/class-opc-db.php';
require_once OPC_PLUGIN_DIR . 'includes/class-opc-utils.php';
require_once OPC_PLUGIN_DIR . 'includes/class-opc-admin.php';
require_once OPC_PLUGIN_DIR . 'includes/class-opc-plugin.php';

register_activation_hook(__FILE__, array('OPC_DB', 'activate'));
register_deactivation_hook(__FILE__, array('OPC_DB', 'deactivate'));

add_action('plugins_loaded', function () {
	OPC_Plugin::instance()->init();
});