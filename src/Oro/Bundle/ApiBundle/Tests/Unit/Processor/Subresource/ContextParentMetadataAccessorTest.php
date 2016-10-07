<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;

class ContextParentMetadataAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var ContextParentMetadataAccessor */
    protected $metadataAccessor;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataAccessor = new ContextParentMetadataAccessor($this->context);
    }

    public function testGetMetadataForContextParentClass()
    {
        $className = 'Test\Entity';
        $metadata = new EntityMetadata();

        $this->context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->context->expects($this->once())
            ->method('getParentMetadata')
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForNotContextParentClass()
    {
        $this->context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn('Test\Entity1');
        $this->context->expects($this->never())
            ->method('getParentMetadata');

        $this->assertNull($this->metadataAccessor->getMetadata('Test\Entity2'));
    }
}
