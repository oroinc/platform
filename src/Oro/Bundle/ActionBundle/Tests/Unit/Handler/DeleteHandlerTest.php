<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Handler;

use Oro\Bundle\ActionBundle\Handler\DeleteHandler;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;

class DeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityDeleteHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $deleteHandlerRegistry;

    /** @var DeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->deleteHandlerRegistry = $this->createMock(EntityDeleteHandlerRegistry::class);

        $this->handler = new DeleteHandler($this->deleteHandlerRegistry);
    }

    public function testHandleDelete()
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
