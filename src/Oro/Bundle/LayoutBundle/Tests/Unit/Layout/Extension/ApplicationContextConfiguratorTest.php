<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\ApplicationContextConfigurator;

class ApplicationContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $kernel;

    /** @var ApplicationContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->contextConfigurator = new ApplicationContextConfigurator($this->kernel);
    }

    protected function tearDown()
    {
        unset($this->contextConfigurator, $this->kernel);
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $this->kernel->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['debug']);
    }

    public function testConfigureContextOverride()
    {
        $context = new LayoutContext();

        $this->kernel->expects($this->never())
            ->method('isDebug');

        $context['debug'] = false;
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context['debug']);
    }
}
