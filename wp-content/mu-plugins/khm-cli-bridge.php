<?php
/**
 * Ensure the 4A scoring WP-CLI command is available in local environments even
 * when the full KHM plugin stack is not activated (due to heavy dependencies).
 */
/*
Plugin Name: KHM CLI Bridge
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$plugin_root = WP_CONTENT_DIR . '/plugins/khm-plugin';
	$autoloader  = $plugin_root . '/vendor/autoload.php';

	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	}

	if ( class_exists( 'KHM\\Cli\\FourAScoreCommand' ) ) {
		WP_CLI::add_command( 'khm-4a', 'KHM\\Cli\\FourAScoreCommand' );
	}
}

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'KHM 4A Scores',
			'KHM 4A Scores',
			'manage_options',
			'khm-4a-scores',
			'khm_cli_bridge_render_scores_page'
		);
	}
);

add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['page'], $_GET['khm_4a_export'] ) || 'khm-4a-scores' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$type = sanitize_key( wp_unslash( $_GET['khm_4a_export'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		khm_cli_bridge_output_csv( $type );
	}
);

/**
 * Render the admin page showing recent person/company scores.
 */
function khm_cli_bridge_render_scores_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'khm-membership' ) );
	}

	global $wpdb;
	$person_table  = khm_cli_bridge_resolve_table( $wpdb, 'cp_scores_person' );
	$company_table = khm_cli_bridge_resolve_table( $wpdb, 'cp_scores_company' );

	$people   = $person_table ? $wpdb->get_results( "SELECT actor_email, person_score, stage, last_touch, last_touch_at, mql_flag, sql_flag, updated_at FROM {$person_table} ORDER BY updated_at DESC LIMIT 25" ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$companies = $company_table ? $wpdb->get_results( "SELECT company_domain, company_score, stage_mode, engaged_contacts, hot_flag, hot_since, updated_at FROM {$company_table} ORDER BY updated_at DESC LIMIT 25" ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'KHM 4A Scores', 'khm-membership' ); ?></h1>
		<?php if ( empty( $person_table ) && empty( $company_table ) ) : ?>
			<p><?php esc_html_e( 'cp_scores_* tables not found. Run migrations before using this report.', 'khm-membership' ); ?></p>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( $person_table ) : ?>
			<h2><?php esc_html_e( 'Recent People Scores', 'khm-membership' ); ?></h2>
			<p><a class="button" href="<?php echo esc_url( add_query_arg( 'khm_4a_export', 'person' ) ); ?>"><?php esc_html_e( 'Download CSV', 'khm-membership' ); ?></a></p>
			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Actor Email', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Score', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Stage', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Last Touch', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'MQL', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'SQL', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'khm-membership' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $people ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No rows found.', 'khm-membership' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $people as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->actor_email ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->person_score, 2 ) ); ?></td>
							<td><?php echo esc_html( $row->stage ); ?></td>
							<td>
								<?php
								if ( $row->last_touch ) {
									echo esc_html( $row->last_touch );
									if ( $row->last_touch_at ) {
										echo '<br><small>' . esc_html( $row->last_touch_at ) . '</small>';
									}
								}
								?>
							</td>
							<td><?php echo $row->mql_flag ? '✔' : '—'; ?></td>
							<td><?php echo $row->sql_flag ? '✔' : '—'; ?></td>
							<td><?php echo esc_html( $row->updated_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( $company_table ) : ?>
			<h2 style="margin-top:2em;"><?php esc_html_e( 'Recent Company Scores', 'khm-membership' ); ?></h2>
			<p><a class="button" href="<?php echo esc_url( add_query_arg( 'khm_4a_export', 'company' ) ); ?>"><?php esc_html_e( 'Download CSV', 'khm-membership' ); ?></a></p>
			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Company', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Score', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Stage', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Engaged Contacts (21d)', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Hot Flag', 'khm-membership' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'khm-membership' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $companies ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No rows found.', 'khm-membership' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $companies as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->company_domain ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->company_score, 2 ) ); ?></td>
							<td><?php echo esc_html( $row->stage_mode ); ?></td>
							<td><?php echo esc_html( (int) $row->engaged_contacts ); ?></td>
							<td><?php echo $row->hot_flag ? '🔥' : '—'; ?></td>
							<td><?php echo esc_html( $row->updated_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Stream a CSV export for the requested score type.
 *
 * @param string $type person|company.
 */
function khm_cli_bridge_output_csv( string $type ): void {
	global $wpdb;

	if ( 'person' === $type ) {
		$table = khm_cli_bridge_resolve_table( $wpdb, 'cp_scores_person' );
		$rows  = $table ? $wpdb->get_results( "SELECT actor_email, score_date, person_score, stage, last_touch, last_touch_at, mql_flag, sql_flag, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT 200", ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$filename = 'khm-person-scores.csv';
	} elseif ( 'company' === $type ) {
		$table = khm_cli_bridge_resolve_table( $wpdb, 'cp_scores_company' );
		$rows  = $table ? $wpdb->get_results( "SELECT company_domain, score_date, company_score, stage_mode, engaged_contacts, hot_flag, hot_since, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT 200", ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$filename = 'khm-company-scores.csv';
	} else {
		wp_die( esc_html__( 'Unknown export type.', 'khm-membership' ) );
	}

	if ( empty( $table ) ) {
		wp_die( esc_html__( 'Scores table not found.', 'khm-membership' ) );
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );

	$output = fopen( 'php://output', 'w' );
	if ( ! empty( $rows ) ) {
		fputcsv( $output, array_keys( $rows[0] ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}
	} else {
		fputcsv( $output, array( 'message' => 'No records found.' ) );
	}
	fclose( $output );
	exit;
}

/**
 * Resolve prefixed table name if it exists.
 *
 * @param \wpdb  $wpdb DB object.
 * @param string $base Base table name without prefix.
 * @return string|null
 */
function khm_cli_bridge_resolve_table( \wpdb $wpdb, string $base ): ?string {
	$prefixed = $wpdb->prefix . $base;
	$found    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefixed ) );
	if ( $found === $prefixed ) {
		return $prefixed;
	}

	$bare = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $base ) );
	return ( $bare === $base ) ? $base : null;
}
