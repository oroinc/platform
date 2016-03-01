<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class NormalizeEntityIdTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var NormalizeEntityId */
    protected $processor;

    protected function setUp()
    {
        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new NormalizeEntityId($this->entityIdTransformer);
    }

    public function testProcessWhenIdAlreadyNormalized()
    {
        $context = $this->getContext();
        $context->setClassName('Test\Class');
        $context->setId(123);

        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->processor->process($context);
    }

    public function testProcess()
    {
        $context = $this->getContext();
        $context->setClassName('Test\Class');
        $context->setId('123');

        $this->entityIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with('Test\Class', '123')
            ->willReturn(123);

        $this->processor->process($context);

        $this->assertSame(123, $context->getId());
    }

    /**
     * @return SingleItemContext
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new SingleItemContext($configProvider, $metadataProvider);
    }
}
