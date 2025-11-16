<?php

use KHM\Preview\Token\TokenGenerator;
use PHPUnit\Framework\TestCase;

class TokenGeneratorTest extends TestCase {
    public function test_generate_returns_hex_string_of_expected_length(): void {
        $generator = new TokenGenerator( function () {
            return 'secret';
        } );

        $token = $generator->generate( 8 );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]+$/', $token );
        $this->assertSame( 16, strlen( $token ) );
    }

    public function test_hash_token_is_deterministic_for_same_secret(): void {
        $generator = new TokenGenerator( function () {
            return 'secret';
        } );

        $token = 'foo';
        $hash1 = $generator->hash_token( $token );
        $hash2 = $generator->hash_token( $token );

        $this->assertSame( $hash1, $hash2 );
    }

    public function test_default_secret_stores_option_when_missing(): void {
        $generator = new TokenGenerator();
        $secret    = $generator->default_secret();

        $this->assertNotEmpty( $secret );
        $this->assertSame( $secret, $GLOBALS['khm_preview_test_options']['khm_preview_secret'] );
    }
}
