<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\CreateEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CreateEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityInstantiator;

    /** @var CreateEntity */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->entityInstantiator = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\EntityInstantiator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CreateEntity($this->entityInstantiator);
    }

    public function testProcess()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $entity = new $entityClass();

        $this->entityInstantiator->expects($this->once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $this->assertSame($entity, $this->context->getResult());
    }

    public function testProcessWhenEntityIsAlreadyCreated()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $entity = new $entityClass();

        $this->entityInstantiator->expects($this->never())
            ->method('instantiate');

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertSame($entity, $this->context->getResult());
    }
}
