<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;

class ContextMetadataAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var ContextMetadataAccessor */
    protected $metadataAccessor;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataAccessor = new ContextMetadataAccessor($this->context);
    }

    public function testGetMetadataForContextClass()
    {
        $className = 'Test\Entity';
        $metadata = new EntityMetadata();

        $this->context->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForNotContextClass()
    {
        $this->context->expects($this->once())
            ->method('getClassName')
            ->willReturn('Test\Entity1');
        $this->context->expects($this->never())
            ->method('getMetadata');

        $this->assertNull($this->metadataAccessor->getMetadata('Test\Entity2'));
    }
}
