<?php
if (!defined('ABSPATH')) {
	exit;
}

final class OPC_Admin {
	public static function init(): void {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
		add_action('admin_init', array(__CLASS__, 'register_settings'));
	}

	public static function admin_menu(): void {
		add_options_page(
			'Optimal Page Counter',
			'Optimal Page Counter',
			'manage_options',
			'opc-settings',
			array(__CLASS__, 'render_settings_page')
		);
	}

	public static function register_settings(): void {
		register_setting('opc_settings_group', 'opc_settings', array(
			'type' => 'array',
			'sanitize_callback' => array(__CLASS__, 'sanitize_settings'),
			'default' => OPC_Utils::default_settings(),
		));
	}

	public static function sanitize_settings($input): array {
		$defaults = OPC_Utils::default_settings();
		$out = $defaults;

		if (!is_array($input)) {
			return $out;
		}

		$out['enabled'] = !empty($input['enabled']);
		$out['count_singular_only'] = !empty($input['count_singular_only']);

		$out['ignore_bots'] = !empty($input['ignore_bots']);
		$out['ignore_admin'] = !empty($input['ignore_admin']);
		$out['ignore_preview'] = !empty($input['ignore_preview']);
		$out['ignore_ajax'] = !empty($input['ignore_ajax']);
		$out['ignore_rest'] = !empty($input['ignore_rest']);

		$out['show_admin_column'] = !empty($input['show_admin_column']);

		// Post types
		$pts = $input['count_post_types'] ?? array();
		if (is_array($pts)) {
			$out['count_post_types'] = array_values(array_filter(array_map('sanitize_key', $pts)));
		}

		// Roles
		$roles = $input['exclude_roles'] ?? array();
		if (is_array($roles)) {
			$out['exclude_roles'] = array_values(array_filter(array_map('sanitize_key', $roles)));
		}

		// IP list (textarea)
		$ips = $input['exclude_ips'] ?? '';
		$ips = is_string($ips) ? $ips : '';
		$ips = preg_replace('/[^\P{C}\t\r\n]+/u', '', $ips);
		$out['exclude_ips'] = trim((string) $ips);

		return $out;
	}

	public static function render_settings_page(): void {
		if (!current_user_can('manage_options')) {
			return;
		}

		$s = OPC_Utils::get_settings();
		$roles = OPC_Utils::all_wp_roles();
		$post_types = get_post_types(array('public' => true), 'objects');
		?>
		<div class="wrap">
			<h1>Optimal Page Counter</h1>

			<form method="post" action="options.php">
				<?php settings_fields('opc_settings_group'); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Aktiverad</th>
						<td>
							<label>
								<input type="checkbox" name="opc_settings[enabled]" value="1" <?php checked(!empty($s['enabled'])); ?> />
								Räkna visningar
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">Räkna endast singular</th>
						<td>
							<label>
								<input type="checkbox" name="opc_settings[count_singular_only]" value="1" <?php checked(!empty($s['count_singular_only'])); ?> />
								Endast inlägg/sidor (inte arkiv, sök, etc.)
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">Post types</th>
						<td>
							<?php foreach ($post_types as $pt): ?>
								<label style="display:block; margin:4px 0;">
									<input
										type="checkbox"
										name="opc_settings[count_post_types][]"
										value="<?php echo esc_attr($pt->name); ?>"
										<?php checked(in_array($pt->name, $s['count_post_types'], true)); ?>
									/>
									<?php echo esc_html($pt->labels->singular_name . " ({$pt->name})"); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>

					<tr>
						<th scope="row">Exkludera roller</th>
						<td>
							<?php foreach ($roles as $role_key => $role): ?>
								<label style="display:block; margin:4px 0;">
									<input
										type="checkbox"
										name="opc_settings[exclude_roles][]"
										value="<?php echo esc_attr($role_key); ?>"
										<?php checked(in_array($role_key, $s['exclude_roles'], true)); ?>
									/>
									<?php echo esc_html($role['name'] . " ({$role_key})"); ?>
								</label>
							<?php endforeach; ?>
							<p class="description">Inloggade användare med dessa roller räknas inte.</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Exkludera IP</th>
						<td>
							<textarea name="opc_settings[exclude_ips]" rows="8" class="large-text code"><?php echo esc_textarea($s['exclude_ips']); ?></textarea>
							<p class="description">
								En IP per rad. Wildcard: <code>192.168.*</code>. Rader med <code>#</code> ignoreras.<br>
								Nuvarande IP (REMOTE_ADDR): <code><?php echo esc_html(OPC_Utils::get_client_ip()); ?></code>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Ignorera bots</th>
						<td>
							<label>
								<input type="checkbox" name="opc_settings[ignore_bots]" value="1" <?php checked(!empty($s['ignore_bots'])); ?> />
								Filtrera kända bots via User-Agent
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">Ignorera admin/preview/AJAX/REST</th>
						<td>
							<label style="display:block; margin:4px 0;">
								<input type="checkbox" name="opc_settings[ignore_admin]" value="1" <?php checked(!empty($s['ignore_admin'])); ?> />
								Ignorera wp-admin
							</label>
							<label style="display:block; margin:4px 0;">
								<input type="checkbox" name="opc_settings[ignore_preview]" value="1" <?php checked(!empty($s['ignore_preview'])); ?> />
								Ignorera previews
							</label>
							<label style="display:block; margin:4px 0;">
								<input type="checkbox" name="opc_settings[ignore_ajax]" value="1" <?php checked(!empty($s['ignore_ajax'])); ?> />
								Ignorera AJAX
							</label>
							<label style="display:block; margin:4px 0;">
								<input type="checkbox" name="opc_settings[ignore_rest]" value="1" <?php checked(!empty($s['ignore_rest'])); ?> />
								Ignorera REST (vi undantar vår hit-endpoint i Milestone 2)
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">Admin QoL</th>
						<td>
							<label>
								<input type="checkbox" name="opc_settings[show_admin_column]" value="1" <?php checked(!empty($s['show_admin_column'])); ?> />
								Visa Views-kolumn (kommer senare)
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button('Spara inställningar'); ?>
			</form>
		</div>
		<?php
	}
}