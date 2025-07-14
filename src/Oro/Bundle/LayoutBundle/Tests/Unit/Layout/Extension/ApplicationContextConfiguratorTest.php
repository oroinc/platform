<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ApplicationContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationContextConfiguratorTest extends TestCase
{
    private KernelInterface&MockObject $kernel;
    private ApplicationContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->contextConfigurator = new ApplicationContextConfigurator($this->kernel);
    }

    public function testConfigureContext(): void
    {
        $context = new LayoutContext();

        $this->kernel->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['debug']);
    }

    public function testConfigureContextOverride(): void
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
