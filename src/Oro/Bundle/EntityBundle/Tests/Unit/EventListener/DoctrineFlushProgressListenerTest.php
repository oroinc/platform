<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;

class DoctrineFlushProgressListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineFlushProgressListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new DoctrineFlushProgressListener();
    }

    public function testIsFlushInProgressWithoutAnyDoctrineCalls()
    {
        $em = $this->createMock(EntityManager::class);
        $this->assertFalse($this->listener->isFlushInProgress($em));
    }

    public function testIsFlushInProgress()
    {
        $em = $this->createMock(EntityManager::class);
        $preFlushEvent = new PreFlushEventArgs($em);
        $this->listener->preFlush($preFlushEvent);

        $this->assertTrue($this->listener->isFlushInProgress($em));

        $postFlushEvent = new PostFlushEventArgs($em);
        $this->listener->postFlush($postFlushEvent);

        $this->assertFalse($this->listener->isFlushInProgress($em));
    }

    public function testIsFlushInProgressHandlesDifferentEntityManagers()
    {
        $em1 = $this->createMock(EntityManager::class);
        $preFlushEvent = new PreFlushEventArgs($em1);
        $this->listener->preFlush($preFlushEvent);

        $this->assertTrue($this->listener->isFlushInProgress($em1));

        $em2 = $this->createMock(EntityManager::class);
        $this->assertFalse($this->listener->isFlushInProgress($em2));
    }
}
