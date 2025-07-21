<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use PHPUnit\Framework\TestCase;

class DoctrineFlushProgressListenerTest extends TestCase
{
    private DoctrineFlushProgressListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new DoctrineFlushProgressListener();
    }

    public function testIsFlushInProgressWithoutAnyDoctrineCalls(): void
    {
        $em = $this->createMock(EntityManager::class);
        $this->assertFalse($this->listener->isFlushInProgress($em));
    }

    public function testIsFlushInProgress(): void
    {
        $em = $this->createMock(EntityManager::class);
        $preFlushEvent = new PreFlushEventArgs($em);
        $this->listener->preFlush($preFlushEvent);

        $this->assertTrue($this->listener->isFlushInProgress($em));

        $postFlushEvent = new PostFlushEventArgs($em);
        $this->listener->postFlush($postFlushEvent);

        $this->assertFalse($this->listener->isFlushInProgress($em));
    }

    public function testIsFlushInProgressHandlesDifferentEntityManagers(): void
    {
        $em1 = $this->createMock(EntityManager::class);
        $preFlushEvent = new PreFlushEventArgs($em1);
        $this->listener->preFlush($preFlushEvent);

        $this->assertTrue($this->listener->isFlushInProgress($em1));

        $em2 = $this->createMock(EntityManager::class);
        $this->assertFalse($this->listener->isFlushInProgress($em2));
    }
}
