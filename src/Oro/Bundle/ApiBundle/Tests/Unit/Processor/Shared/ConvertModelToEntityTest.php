<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\ConvertModelToEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class ConvertModelToEntityTest extends FormProcessorTestCase
{
    /** @var ConvertModelToEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ConvertModelToEntity();
    }

    public function testProcessWhenNoModel()
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::never())
            ->method('getEntity');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoEntityMapper()
    {
        $entityClass = Entity\User::class;
        $model = new \stdClass();
        $config = new EntityDefinitionConfig();

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setResult($model);
        $this->processor->process($this->context);

        self::assertSame($model, $this->context->getResult());
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

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setResult($model);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
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

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setResult($model);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
