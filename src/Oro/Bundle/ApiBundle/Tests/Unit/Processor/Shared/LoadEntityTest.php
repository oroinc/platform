<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class LoadEntityTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityLoader */
    protected $entityLoader;

    /** @var LoadEntity */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(EntityLoader::class);

        $this->processor = new LoadEntity(
            $this->doctrineHelper,
            $this->entityLoader
        );
    }

    public function testProcessWhenEntityAlreadyLoaded()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntity()
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $entity = new \stdClass();
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, $metadata)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
