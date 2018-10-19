<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ConvertParentEntityToModel;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class ConvertParentEntityToModelTest extends ChangeRelationshipProcessorTestCase
{
    /** @var ConvertParentEntityToModel */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ConvertParentEntityToModel();
    }

    public function testProcessWhenNoEntity()
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::never())
            ->method('getModel');

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessWhenNoEntityMapper()
    {
        $entityClass = Entity\User::class;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setParentEntity($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }

    public function testProcessWhenEntityShouldBeConvertedToModel()
    {
        $entityClass = Entity\User::class;
        $entity = new \stdClass();
        $model = new \stdClass();
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::once())
            ->method('getModel')
            ->with(self::identicalTo($entity), $entityClass)
            ->willReturn($model);

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setParentEntity($entity);
        $this->processor->process($this->context);

        self::assertSame($model, $this->context->getParentEntity());
    }
}
