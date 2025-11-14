<?php
class KH_Bounce_Activation_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        delete_option( 'kh_bounce_settings' );
    }

    public function test_activation_sets_defaults() {
        kh_bounce_activate();
        $settings = get_option( 'kh_bounce_settings' );
        $this->assertArrayHasKey( 'status', $settings );
        $this->assertSame( 'classic', $settings['template'] );
        $this->assertSame( '0', $settings['show_on_mobile'] );
        $this->assertSame( '0', $settings['test_mode'] );
        $this->assertSame( 'none', $settings['telemetry_mode'] );
    }
}
