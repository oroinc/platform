<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;

class ContextConfigAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var ContextConfigAccessor */
    protected $configAccessor;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configAccessor = new ContextConfigAccessor($this->context);
    }

    public function testGetConfigForContextClass()
    {
        $className = 'Test\Entity';
        $config = new EntityDefinitionConfig();

        $this->context->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForNotContextClass()
    {
        $this->context->expects($this->once())
            ->method('getClassName')
            ->willReturn('Test\Entity1');
        $this->context->expects($this->never())
            ->method('getConfig');

        $this->assertNull($this->configAccessor->getConfig('Test\Entity2'));
    }
}
