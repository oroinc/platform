<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class NormalizeEntityIdTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var NormalizeEntityId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new NormalizeEntityId($this->entityIdTransformer);
    }

    public function testProcessWhenIdAlreadyNormalized()
    {
        $this->context->setClassName('Test\Class');
        $this->context->setId(123);

        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $this->context->setClassName('Test\Class');
        $this->context->setId('123');

        $this->entityIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with($this->context->getClassName(), $this->context->getId())
            ->willReturn(123);

        $this->processor->process($this->context);

        $this->assertSame(123, $this->context->getId());
    }

    public function testProcessForInvalidId()
    {
        $this->context->setClassName('Test\Class');
        $this->context->setId('123');

        $this->entityIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with($this->context->getClassName(), $this->context->getId())
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        $this->assertSame('123', $this->context->getId());
        $this->assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('some error'))
            ],
            $this->context->getErrors()
        );
    }
}
