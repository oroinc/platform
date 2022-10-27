<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ApplicationContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var ApplicationContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->contextConfigurator = new ApplicationContextConfigurator($this->kernel);
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
