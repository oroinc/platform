<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\CreateEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CreateEntityTest extends FormProcessorTestCase
{
    /** @var CreateEntity */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->processor = new CreateEntity();
    }

    public function testProcess()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $this->assertInstanceOf($entityClass, $this->context->getResult());
    }

    public function testProcessWhenEntityIsAlreadyCreated()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $entity = new $entityClass();

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertSame($entity, $this->context->getResult());
    }
}
