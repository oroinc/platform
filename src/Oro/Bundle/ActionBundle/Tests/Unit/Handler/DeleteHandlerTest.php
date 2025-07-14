<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Handler;

use Oro\Bundle\ActionBundle\Handler\DeleteHandler;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteHandlerTest extends TestCase
{
    private EntityDeleteHandlerRegistry&MockObject $deleteHandlerRegistry;
    private DeleteHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->deleteHandlerRegistry = $this->createMock(EntityDeleteHandlerRegistry::class);

        $this->handler = new DeleteHandler($this->deleteHandlerRegistry);
    }

    public function testHandleDelete(): void
    {
        $entity = new TestEntity1();

        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);
        $this->deleteHandlerRegistry->expects($this->once())
            ->method('getHandler')
            ->with(get_class($entity))
            ->willReturn($deleteHandler);
        $deleteHandler->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($entity));

        $this->handler->handleDelete($entity);
    }
}
