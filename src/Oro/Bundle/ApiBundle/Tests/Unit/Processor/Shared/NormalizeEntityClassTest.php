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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesCache;

    /** @var NormalizeEntityClass */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesLoader = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesLoader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesCache = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityClass(
            $this->valueNormalizer,
            $this->resourcesLoader,
            $this->resourcesCache
        );
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
        $this->resourcesCache->expects($this->once())
            ->method('getAccessibleResources')
            ->with($this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['Test\Class']);

        $this->processor->process($this->context);

        $this->assertSame('Test\Class', $this->context->getClassName());
    }

    public function testProcessWhenNoResourcesCache()
    {
        $this->context->setClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesCache->expects($this->at(0))
            ->method('getAccessibleResources')
            ->with($this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);
        $this->resourcesLoader->expects($this->once())
            ->method('getResources')
            ->with($this->context->getVersion(), $this->context->getRequestType());
        $this->resourcesCache->expects($this->at(1))
            ->method('getAccessibleResources')
            ->with($this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['Test\Class']);

        $this->processor->process($this->context);

        $this->assertSame('Test\Class', $this->context->getClassName());
    }

    public function testProcessForNotAccessibleEntityType()
    {
        $this->context->setClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesCache->expects($this->once())
            ->method('getAccessibleResources')
            ->with($this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getClassName());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown entity type: test.')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForInvalidEntityType()
    {
        $this->context->setClassName('test');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($this->context->getClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        $this->assertNull($this->context->getClassName());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown entity type: test.')
            ],
            $this->context->getErrors()
        );
    }
}
