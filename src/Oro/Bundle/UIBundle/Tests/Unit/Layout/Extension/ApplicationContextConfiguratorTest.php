<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\UIBundle\Layout\Extension\ApplicationContextConfigurator;

use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelInterface */
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

        $this->assertTrue($context->getDataResolver()->isKnown('debug'));
        $this->assertTrue($context['debug']);
    }
}
