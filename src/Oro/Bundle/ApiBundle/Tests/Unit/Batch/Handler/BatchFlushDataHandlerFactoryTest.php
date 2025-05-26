<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactory;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BatchFlushDataHandlerFactoryTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private FlushDataHandlerInterface&MockObject $flushDataHandler;
    private BatchFlushDataHandlerFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->flushDataHandler = $this->createMock(FlushDataHandlerInterface::class);

        $this->factory = new BatchFlushDataHandlerFactory($this->doctrineHelper, $this->flushDataHandler);
    }

    public function testCreateHandlerForManageableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);

        $handler = $this->factory->createHandler($entityClass);
        self::assertInstanceOf(BatchFlushDataHandler::class, $handler);
        self::assertEquals($entityClass, ReflectionUtil::getPropertyValue($handler, 'entityClass'));
        self::assertSame($this->doctrineHelper, ReflectionUtil::getPropertyValue($handler, 'doctrineHelper'));
        self::assertSame($this->flushDataHandler, ReflectionUtil::getPropertyValue($handler, 'flushDataHandler'));
    }

    public function testCreateHandlerForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);

        self::assertNull($this->factory->createHandler($entityClass));
    }
}
