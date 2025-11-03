<?php
/**
 * The template for displaying footer.
 *
 * @package Touchpoint CRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$footer_nav_menu = wp_nav_menu( [
	'theme_location' => 'menu-2', // Make sure you have a menu set in this location
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
] );
$footer_class = 'custom-footer-class'; // Add any custom class for styling if needed
$footer_logo_display = get_theme_mod( 'footer_logo_display' ); // Example of custom theme mod for logo display
$footer_logo_type = get_theme_mod( 'footer_logo_type' ); // Example of custom theme mod for logo type
$footer_copyright_text = get_theme_mod( 'footer_copyright_text' ); // Example of custom theme mod for copyright text
?>

<footer id="site-footer" class="site-footer <?php echo esc_attr( $footer_class ); ?>">
	<div class="footer-inner">
		<!-- Footer Branding Section -->
		<div class="footer-branding show-<?php echo esc_attr( $footer_logo_type ); ?>">
			<?php if ( has_custom_logo() && ( 'title' !== $footer_logo_type || is_admin() ) ) : ?>
				<div class="footer-logo <?php echo esc_attr( $footer_logo_display ); ?>">
					<?php the_custom_logo(); ?>
				</div>
			<?php endif; ?>

			<?php if ( get_bloginfo( 'name' ) && ( 'logo' !== $footer_logo_type ) || is_admin() ) : ?>
				<div class="footer-site-title <?php echo esc_attr( $footer_logo_display ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr__( 'Home', 'touchpoint-crm' ); ?>" rel="home">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( get_bloginfo( 'description' ) || is_admin() ) : ?>
				<p class="footer-description <?php echo esc_attr( $footer_logo_display ); ?>">
					<?php echo esc_html( get_bloginfo( 'description', 'display' ) ); ?>
				</p>
			<?php endif; ?>
		</div>

		<!-- Footer Navigation Section -->
		<?php if ( $footer_nav_menu ) : ?>
			<nav class="footer-navigation <?php echo esc_attr( $footer_logo_display ); ?>" aria-label="<?php echo esc_attr__( 'Footer menu', 'touchpoint-crm' ); ?>">
				<?php echo $footer_nav_menu; ?>
			</nav>
		<?php endif; ?>

		<!-- Footer Copyright Section -->
		<?php if ( '' !== $footer_copyright_text || is_admin() ) : ?>
			<div class="footer-copyright <?php echo esc_attr( $footer_logo_display ); ?>">
				<p><?php echo wp_kses_post( $footer_copyright_text ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</footer>
