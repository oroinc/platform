<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\NormalizeParentEntityClass;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class NormalizeParentEntityClassTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesProvider;

    /** @var NormalizeParentEntityClass */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeParentEntityClass(
            $this->valueNormalizer,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenParentClassAlreadyNormalized()
    {
        $this->context->setParentClassName('Test\Class');

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $this->processor->process($this->context);
    }

    public function testConvertParentEntityTypeToEntityClass()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects($this->once())
            ->method('isResourceAccessible')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->processor->process($this->context);

        $this->assertSame('Test\Class', $this->context->getParentClassName());
    }

    public function testProcessForNotAccessibleParentEntityType()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects($this->once())
            ->method('isResourceAccessible')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getParentClassName());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown parent entity type: test.')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForInvalidParentEntityType()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        $this->assertNull($this->context->getParentClassName());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown parent entity type: test.')
            ],
            $this->context->getErrors()
        );
    }
}
