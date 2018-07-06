<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\ConvertEntityToModel;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class ConvertEntityToModelTest extends FormProcessorTestCase
{
    /** @var ConvertEntityToModel */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ConvertEntityToModel();
    }

    public function testProcessWhenNoEntity()
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $entityMapper->expects(self::never())
            ->method('getModel');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoEntityMapper()
    {
        $entityClass = Entity\User::class;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
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

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setEntityMapper($entityMapper);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        self::assertSame($model, $this->context->getResult());
    }
}
