<?php

use KHM\Preview\REST\PreviewRestController;
use KHM\Preview\Services\PreviewAnalyticsService;
use KHM\Preview\Services\PreviewLinkService;
use PHPUnit\Framework\TestCase;

class DummyPreviewLinkService extends PreviewLinkService {
    private $link;

    public function __construct() {}

    public function set_link( array $link ): void {
        $this->link = $link;
    }

    public function get_active_link( int $post_id ): ?array {
        return $this->link;
    }

    public function get_link( int $id ): ?array {
        return $this->link;
    }

    public function create_link( int $post_id, int $user_id, \DateTimeImmutable $expires_at, array $meta = [] ): array {
        $token = bin2hex( random_bytes( 4 ) );
        $link  = [
            'id'        => 99,
            'post_id'   => $post_id,
            'token'     => $token,
            'expires_at'=> $expires_at->format( 'Y-m-d H:i:s' ),
            'status'    => 'active',
        ];
        $this->link = $link;
        return $link;
    }
}

class DummyPreviewAnalyticsService extends PreviewAnalyticsService {
    private $hits = [];

    public function __construct() {}

    public function set_hits( array $hits ): void {
        $this->hits = $hits;
    }

    public function get_recent_hits( int $link_id, int $limit = 20 ): array {
        return $this->hits;
    }
}

class PreviewRestControllerTest extends TestCase {
    public function test_get_post_link_response_contains_hits(): void {
        $service   = new DummyPreviewLinkService();
        $analytics = new DummyPreviewAnalyticsService();
        $controller = new PreviewRestController( $service, $analytics );

        $link = [
            'id'        => 10,
            'post_id'   => 55,
            'token'     => 'abcd',
            'expires_at'=> gmdate( 'Y-m-d H:i:s', time() + 3600 ),
            'status'    => 'active',
        ];
        $service->set_link( $link );
        $analytics->set_hits( [ [ 'viewed_at' => '2024-01-01 00:00:00', 'ip' => '127.0.0.1' ] ] );

        $request = new WP_REST_Request( [ 'post_id' => 55 ] );
        $response = $controller->get_post_link( $request );
        $data = $response->get_data();
        $this->assertSame( $link['token'], $data['token'] );
        $this->assertNotEmpty( $data['hits'] );
    }
}
