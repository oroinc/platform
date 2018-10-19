<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ApplicationContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class ApplicationContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $kernel;

    /** @var ApplicationContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');
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
