<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once __DIR__ . '/includes/class-opc-db.php';

// Remove settings
delete_option('opc_settings');

// Optional: drop table on uninstall (uncomment if you want FULL wipe)
// OPC_DB::drop_table();