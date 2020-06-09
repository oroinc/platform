<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactory;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class BatchFlushDataHandlerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var BatchFlushDataHandlerFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->factory = new BatchFlushDataHandlerFactory($this->doctrineHelper);
    }

    public function testCreateHandlerForManageableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);

        self::assertInstanceOf(BatchFlushDataHandler::class, $this->factory->createHandler($entityClass));
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
