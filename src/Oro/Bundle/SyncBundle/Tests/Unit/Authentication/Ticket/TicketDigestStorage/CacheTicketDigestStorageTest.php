<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket\TicketDigestStorage;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage\CacheTicketDigestStorage;

class CacheTicketDigestStorageTest extends \PHPUnit_Framework_TestCase
{
    /** @var CacheTicketDigestStorage */
    private $digestStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    protected function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->digestStorage = new CacheTicketDigestStorage($this->cache);
    }

    public function testSaveTicketDigest()
    {
        $generatedId = '';
        $requestedDigest = 'test_digest';

        $this->cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($id, $digest) use (&$generatedId, $requestedDigest) {
                $generatedId = $id;
                $this->assertEquals($requestedDigest, $digest);
            });

        $result = $this->digestStorage->saveTicketDigest($requestedDigest);
        $this->assertEquals($generatedId, $result);
    }

    public function testGetTicketDigestOnNotSavedDigestId()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('not_saved')
            ->willReturn(false);

        $this->assertEquals('', $this->digestStorage->getTicketDigest('not_saved'));
    }

    public function testGetTicketDigestOnSavedDigestId()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('saved')
            ->willReturn('digest');

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('saved');

        $this->assertEquals('digest', $this->digestStorage->getTicketDigest('saved'));
    }
}
