<?php
namespace KHM\Tests;

use KHM\Services\MembershipNoteService;
use PHPUnit\Framework\TestCase;

class MembershipNoteServiceTest extends TestCase
{
    private MembershipNoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        $this->service = new MembershipNoteService();
    }

    public function testAddNotePersistsAndFetches(): void
    {
        $note = $this->service->addNote( 101, 7, 'First note' );
        $this->assertNotNull( $note );

        $notes = $this->service->getNotes( 101 );
        $this->assertCount( 1, $notes );
        $this->assertSame( 'First note', $notes[0]['content'] );
        $this->assertSame( 7, $notes[0]['author_id'] );
    }

    public function testDeleteNoteRemovesEntry(): void
    {
        $note = $this->service->addNote( 202, 5, 'Remove me' );
        $this->assertNotNull( $note );

        $deleted = $this->service->deleteNote( 202, $note['id'] );
        $this->assertTrue( $deleted );

        $notes = $this->service->getNotes( 202 );
        $this->assertCount( 0, $notes );
    }

    public function testAddNoteIgnoresEmptyContent(): void
    {
        $result = $this->service->addNote( 303, 2, '   ' );
        $this->assertNull( $result );
        $this->assertCount( 0, $this->service->getNotes( 303 ) );
    }
}
