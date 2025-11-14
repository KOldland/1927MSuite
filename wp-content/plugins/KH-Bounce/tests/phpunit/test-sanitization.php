<?php
class KH_Bounce_Sanitization_Test extends WP_UnitTestCase {
    protected $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new KH_Bounce_Admin_Settings( kh_bounce() );
    }

    public function test_invalid_values_fall_back_to_defaults() {
        $input = array(
            'status'         => 'maybe',
            'template'       => 'unknown',
            'title'          => '',
            'text'           => '',
            'cta_label'      => '',
            'cta_url'        => 'notaurl',
            'dismiss_label'  => '',
            'display_on_home'=> '',
            'telemetry_mode' => 'bogus',
        );
        $clean = $this->admin->sanitize_settings( $input );
        $this->assertSame( 'off', $clean['status'] );
        $this->assertSame( 'classic', $clean['template'] );
        $this->assertEmpty( $clean['cta_url'] );
        $this->assertSame( 'none', $clean['telemetry_mode'] );
        $this->assertSame( '0', $clean['display_on_home'] );
        $this->assertSame( '0', $clean['show_on_mobile'] );
        $this->assertSame( '0', $clean['test_mode'] );
    }
}
