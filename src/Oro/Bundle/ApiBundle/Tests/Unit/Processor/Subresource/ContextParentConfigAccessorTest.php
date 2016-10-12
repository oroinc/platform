<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;

class ContextParentConfigAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var ContextParentConfigAccessor */
    protected $configAccessor;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configAccessor = new ContextParentConfigAccessor($this->context);
    }

    public function testGetConfigForContextParentClass()
    {
        $className = 'Test\Entity';
        $config = new EntityDefinitionConfig();

        $this->context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->context->expects($this->once())
            ->method('getParentConfig')
            ->willReturn($config);

        $this->assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForNotContextParentClass()
    {
        $this->context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn('Test\Entity1');
        $this->context->expects($this->never())
            ->method('getParentConfig');

        $this->assertNull($this->configAccessor->getConfig('Test\Entity2'));
    }
}
