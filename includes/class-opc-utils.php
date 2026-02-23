<?php
if (!defined('ABSPATH')) {
	exit;
}

final class OPC_Utils {
	public static function default_settings(): array {
		return array(
			'enabled' => true,

			// Counting scope
			'count_post_types' => array('post', 'page'),
			'count_singular_only' => true,

			// Exclusions
			'exclude_roles' => array('administrator', 'editor'),
			'exclude_ips' => "", // newline separated, supports wildcards e.g. 192.168.*
			'ignore_bots' => true,
			'ignore_admin' => true,
			'ignore_preview' => true,
			'ignore_ajax' => true,
			'ignore_rest' => false, // In Milestone 2 we'll exempt our endpoint.

			// QoL toggles (future milestones)
			'show_admin_column' => true,
		);
	}

	public static function get_settings(): array {
		$raw = get_option('opc_settings', self::default_settings());
		if (!is_array($raw)) {
			return self::default_settings();
		}
		return array_merge(self::default_settings(), $raw);
	}

	public static function get_client_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		return is_string($ip) ? trim($ip) : '';
	}

	public static function ip_matches_pattern(string $ip, string $pattern): bool {
		$ip = trim($ip);
		$pattern = trim($pattern);
		if ($ip === '' || $pattern === '') {
			return false;
		}

		// Wildcard support like 192.168.*
		if (str_contains($pattern, '*')) {
			$re = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
			return (bool) preg_match($re, $ip);
		}

		return hash_equals($pattern, $ip);
	}

	public static function is_bot_user_agent(?string $ua): bool {
		if ($ua === null || $ua === '') {
			return false;
		}
		$ua_lc = strtolower($ua);

		// Light heuristic (keep fast). We can make this configurable later.
		$needles = array(
			'bot', 'spider', 'crawl', 'slurp', 'mediapartners-google',
			'facebookexternalhit', 'bingpreview', 'headless', 'lighthouse',
			'python-requests', 'wget', 'curl',
		);

		foreach ($needles as $n) {
			if (str_contains($ua_lc, $n)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Core exclusion logic used by upcoming hit endpoint / PHP fallback.
	 */
	public static function is_excluded_request(int $post_id = 0): bool {
		$s = self::get_settings();

		if (empty($s['enabled'])) {
			return true;
		}

		if (!empty($s['ignore_admin']) && is_admin()) {
			return true;
		}

		if (!empty($s['ignore_preview']) && is_preview()) {
			return true;
		}

		if (!empty($s['ignore_ajax']) && wp_doing_ajax()) {
			return true;
		}

		if (!empty($s['ignore_rest']) && defined('REST_REQUEST') && REST_REQUEST) {
			return true;
		}

		if (!empty($s['count_singular_only']) && !is_singular()) {
			return true;
		}

		// Post type filter if post_id is known
		if ($post_id > 0 && !empty($s['count_post_types']) && is_array($s['count_post_types'])) {
			$pt = get_post_type($post_id);
			if ($pt && !in_array($pt, $s['count_post_types'], true)) {
				return true;
			}
		}

		// Role exclusion
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			$excluded_roles = is_array($s['exclude_roles'] ?? null) ? $s['exclude_roles'] : array();
			if (!empty($excluded_roles) && !empty($user->roles)) {
				if (array_intersect($excluded_roles, $user->roles)) {
					return true;
				}
			}
		}

		// Bot exclusion
		if (!empty($s['ignore_bots'])) {
			$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
			if (self::is_bot_user_agent(is_string($ua) ? $ua : '')) {
				return true;
			}
		}

		// IP exclusion (runtime only; nothing stored)
		$ip_list = is_string($s['exclude_ips'] ?? null) ? $s['exclude_ips'] : '';
		$ip_list = trim($ip_list);
		if ($ip_list !== '') {
			$client_ip = self::get_client_ip();
			$lines = preg_split('/\R/', $ip_list) ?: array();

			foreach ($lines as $line) {
				$line = trim($line);
				if ($line === '' || str_starts_with($line, '#')) {
					continue;
				}
				if (self::ip_matches_pattern($client_ip, $line)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function all_wp_roles(): array {
		global $wp_roles;
		if (!($wp_roles instanceof WP_Roles)) {
			$wp_roles = wp_roles();
		}
		return $wp_roles->roles ?? array();
	}
}