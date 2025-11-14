<?php
class KH_Bounce_Renderer_Test extends WP_UnitTestCase {
    public function test_modal_markup_outputs_title() {
        $plugin = kh_bounce();
        $plugin->save_settings( array(
            'status'          => 'on',
            'template'        => 'classic',
            'title'           => 'Test Title',
            'text'            => 'Body copy',
            'cta_label'       => 'Click Me',
            'cta_url'         => 'https://example.com',
            'dismiss_label'   => 'Nope',
            'display_on_home' => '0',
            'telemetry_mode'  => 'none',
        ) );

        ob_start();
        do_action( 'wp_footer' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Test Title', $output );
        $this->assertStringContainsString( 'Click Me', $output );
    }
}
