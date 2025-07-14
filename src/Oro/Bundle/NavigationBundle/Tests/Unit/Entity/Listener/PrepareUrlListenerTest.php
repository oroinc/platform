<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NavigationBundle\Entity\Listener\PrepareUrlListener;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\UrlAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrepareUrlListenerTest extends TestCase
{
    private EntityManager&MockObject $entityManager;
    private PrepareUrlListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->listener = new PrepareUrlListener();
    }

    public function testPrePersist(): void
    {
        $entity = new UrlAwareStub('url_too_long');

        $args = new LifecycleEventArgs($entity, $this->entityManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getFieldMapping')
            ->with('url')
            ->willReturn(['length' => 3]);
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(UrlAwareStub::class)
            ->willReturn($metadata);

        $this->listener->prePersist($entity, $args);

        $this->assertEquals('url', $entity->getUrl());
    }

    public function testPreUpdate(): void
    {
        $entity = new UrlAwareStub('url_too_long');

        $args = new LifecycleEventArgs($entity, $this->entityManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getFieldMapping')
            ->with('url')
            ->willReturn(['length' => 3]);
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(UrlAwareStub::class)
            ->willReturn($metadata);

        $this->listener->preUpdate($entity, $args);

        $this->assertEquals('url', $entity->getUrl());
    }
}
