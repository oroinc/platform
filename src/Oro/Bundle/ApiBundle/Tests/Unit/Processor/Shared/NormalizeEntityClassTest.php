<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntityClass;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizeEntityClassTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var NormalizeEntityClass */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityClass($this->valueNormalizer);
    }

    public function testProcessWhenClassAlreadyNormalized()
    {
        $this->context->setClassName('Test\Class');

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $this->context->setClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');

        $this->processor->process($this->context);

        $this->assertSame('Test\Class', $this->context->getClassName());
    }

    public function testProcessForInvalidEntityType()
    {
        $this->context->setClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        $this->assertSame('test', $this->context->getClassName());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint')
            ],
            $this->context->getErrors()
        );
    }
}
