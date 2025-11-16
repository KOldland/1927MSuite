<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

$tabs = array(
	'general' => array(
		'label' => __( 'General', 'slicewp' ),
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>'
	),
	'integrations' => array(
		'label' => __( 'Integrations', 'slicewp' ),
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>'
	),
	'emails' => array(
		'label' => __( 'Email Notifications', 'slicewp' ),
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>'
	),
	'tools' => array(
		'label' => __( 'Tools', 'slicewp' ),
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>'
	)
);

/**
 * Filter the tabs for the settings edit screen.
 *
 * @param array $tabs
 *
 */
$tabs = apply_filters( 'slicewp_submenu_page_settings_tabs', $tabs );

$active_tab = ( ! empty( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general' );

/**
 * Prepare the Email Notification Settings section.
 *
 * @param array $tabs
 *
 */
$email_notifications 		   = slicewp_get_available_email_notifications();
$first_email_notification_slug = array_keys( $email_notifications )[0];

$selected_email_notification = ( ! empty( $_GET['email_notification'] ) ? sanitize_text_field( $_GET['email_notification'] ) : $first_email_notification_slug );

/**
 * Prepare the needed variables.
 *
 */
$user 	 = wp_get_current_user();
$user_id = $user->ID;
$affiliate 	  = slicewp_get_affiliate_by_user_id( $user_id );
$affiliate_id = absint( empty( $affiliate ) ? $user_id : $affiliate->get( 'id' ) );

?>

<div class="wrap slicewp-wrap slicewp-wrap-settings">

	<form action="" method="POST">

		<!-- Page Heading -->
		<h1 class="wp-heading-inline"><?php echo __( 'Settings', 'slicewp' ); ?></h1>
		<hr class="wp-header-end" />

		<!-- Tab Navigation -->
		<div class="slicewp-card">

			<!-- Navigation Tab Links -->
			<ul class="slicewp-nav-tab-wrapper">
				<?php 
					foreach ( $tabs as $tab_slug => $tab ) {
						echo '<li class="slicewp-nav-tab ' . ( $tab_slug == $active_tab ? 'slicewp-active' : '' ) . '" data-tab="' . esc_attr( $tab_slug ) . '">';

							echo '<a href="#">';

								// Icon.
								if ( strpos( $tab['icon'], 'dashicons-' ) === 0 ) {
									echo '<span class="dashicons ' . esc_attr( $tab['icon'] ) . '"></span>';
								} else {
									echo wp_kses( $tab['icon'], slicewp_get_kses_allowed_html() );
								}

								// Label.
								echo esc_html( $tab['label'] );

							echo '</a>';
						
						echo '</li>';
					}
				?>
			</ul>

			<!-- Hidden active tab -->
			<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

		</div>


		<?php foreach ( $tabs as $tab_slug => $tab ): ?>

			<div class="slicewp-tab <?php echo ( $active_tab == $tab_slug ? 'slicewp-active' : '' ); ?>" data-tab="<?php echo esc_attr( $tab_slug ); ?>">

				<?php

					if ( file_exists( plugin_dir_path( __FILE__ ) . 'view-settings-tab-' . $tab_slug . '.php' ) ) {

						include_once plugin_dir_path( __FILE__ ) . 'view-settings-tab-' . $tab_slug . '.php';
					
					} else {

						/**
						 * Hook to add additional settings tab content.
						 *
						 */
						do_action( 'slicewp_view_settings_tab_' . $tab_slug );

					}
				
				?>

			</div>

		<?php endforeach; ?>

		<!-- Action and nonce -->
		<input type="hidden" name="slicewp_action" value="save_settings" />
		<?php wp_nonce_field( 'slicewp_save_settings', 'slicewp_token', false ); ?>

	</form>

</div>