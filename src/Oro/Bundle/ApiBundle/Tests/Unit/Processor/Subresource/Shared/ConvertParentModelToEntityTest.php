<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ConvertParentModelToEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class ConvertParentModelToEntityTest extends ChangeRelationshipProcessorTestCase
{
    /** @var ConvertParentModelToEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ConvertParentModelToEntity();
    }

    public function testProcessWhenNoModel()
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::never())
            ->method('getEntity');

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessWhenNoEntityMapper()
    {
        $entityClass = Entity\User::class;
        $model = new \stdClass();
        $config = new EntityDefinitionConfig();

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setParentEntity($model);
        $this->processor->process($this->context);

        self::assertSame($model, $this->context->getParentEntity());
    }

    public function testProcessWhenModelShouldBeConvertedToEntity()
    {
        $entityClass = Entity\User::class;
        $model = new \stdClass();
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::once())
            ->method('getEntity')
            ->with(self::identicalTo($model), $entityClass)
            ->willReturn($entity);

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setParentEntity($model);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }

    public function testProcessWhenModelShouldBeConvertedToEntityForApiResourceBasedOnManageableEntity()
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $model = new \stdClass();
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::once())
            ->method('getEntity')
            ->with(self::identicalTo($model), $parentResourceClass)
            ->willReturn($entity);

        $this->context->setParentClassName($entityClass);
        $this->context->setParentConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setParentEntity($model);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }
}
