<?php

use KHM\Preview\PublicPreviewHandler;
use KHM\Preview\Services\PreviewAnalyticsService;
use KHM\Preview\Token\TokenGenerator;
use KHM\Preview\Database\Repositories\PreviewLinkRepository;
use KHM\Preview\Database\Repositories\PreviewHitRepository;
use PHPUnit\Framework\TestCase;

class StubPreviewLinkRepository extends PreviewLinkRepository {
    private $link;

    public function __construct( array $link ) {
        $this->link = $link;
    }

    public function find_by_token_hash( string $hash ): ?array {
        if ( $hash === $this->link['token_hash'] ) {
            return $this->link;
        }
        return null;
    }
}

class StubPreviewHitRepository extends PreviewHitRepository {
    public $records = [];

    public function __construct() {}

    public function insert( array $data ): int {
        $this->records[] = $data;
        return count( $this->records );
    }
}

class PreviewHandlerTest extends TestCase {
    protected function setUp(): void {
        $_GET = [];
        $GLOBALS['khm_preview_filters'] = [];
        $GLOBALS['khm_preview_posts']   = [];
    }

    public function test_valid_preview_marks_post_published_and_logs_hit(): void {
        $token_generator = new TokenGenerator( function () {
            return 'secret';
        } );
        $token      = 'abc123';
        $token_hash = $token_generator->hash_token( $token );

        $link = [
            'id'        => 1,
            'post_id'   => 42,
            'token'     => $token,
            'token_hash'=> $token_hash,
            'status'    => 'active',
            'expires_at'=> gmdate( 'Y-m-d H:i:s', time() + 3600 ),
        ];

        $repo = new StubPreviewLinkRepository( $link );
        $hit_repo = new StubPreviewHitRepository();
        $analytics = new PreviewAnalyticsService( $hit_repo );
        $handler = new PublicPreviewHandler( $repo, $analytics, $token_generator );

        $_GET['khm_preview_post']  = 42;
        $_GET['khm_preview_token'] = $token;
        $GLOBALS['khm_preview_posts'][42] = (object) [
            'ID'          => 42,
            'post_status' => 'draft',
            'post_password' => '',
        ];

        $handler->maybe_render_preview();
        $this->assertCount( 1, $hit_repo->records, 'Hit should be logged' );
        $this->assertArrayHasKey( 'the_posts', $GLOBALS['khm_preview_filters'] );

        $callback = $GLOBALS['khm_preview_filters']['the_posts'][0];
        $posts    = $callback( [ $GLOBALS['khm_preview_posts'][42] ] );
        $this->assertSame( 'publish', $posts[0]->post_status );
    }

    public function test_invalid_token_triggers_error(): void {
        $this->expectException( WPDieException::class );
        $token_generator = new TokenGenerator( function () {
            return 'secret';
        } );
        $link = [
            'id'        => 1,
            'post_id'   => 42,
            'token'     => 'valid',
            'token_hash'=> $token_generator->hash_token( 'valid' ),
            'status'    => 'active',
            'expires_at'=> gmdate( 'Y-m-d H:i:s', time() + 3600 ),
        ];
        $repo = new StubPreviewLinkRepository( $link );
        $hit_repo = new StubPreviewHitRepository();
        $analytics = new PreviewAnalyticsService( $hit_repo );
        $handler = new PublicPreviewHandler( $repo, $analytics, $token_generator );

        $_GET['khm_preview_post']  = 42;
        $_GET['khm_preview_token'] = 'wrong';

        $handler->maybe_render_preview();
    }
}
