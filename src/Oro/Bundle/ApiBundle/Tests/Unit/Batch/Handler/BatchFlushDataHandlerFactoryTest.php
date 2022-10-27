<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactory;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;

class BatchFlushDataHandlerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FlushDataHandlerInterface */
    private $flushDataHandler;

    /** @var BatchFlushDataHandlerFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->flushDataHandler = $this->createMock(FlushDataHandlerInterface::class);

        $this->factory = new BatchFlushDataHandlerFactory($this->doctrineHelper, $this->flushDataHandler);
    }

    public function testCreateHandlerForManageableEntity()
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

    public function testCreateHandlerForNotManageableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);

        self::assertNull($this->factory->createHandler($entityClass));
    }
}
