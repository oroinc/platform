<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\EmbeddedFormBundle\Layout\Extension\DependencyInjectionFormContextConfigurator;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyInjectionFormContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var DependencyInjectionFormContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->contextConfigurator = new DependencyInjectionFormContextConfigurator($this->container);
    }

    public function testConfigureContextWithoutForm()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('formServiceId should be specified.');

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
    }

    public function testConfigureContext()
    {
        $serviceId = 'test_service_id';
        $contextOptionName = 'test_context_form';
        $this->contextConfigurator->setFormServiceId($serviceId);
        $this->contextConfigurator->setContextOptionName($contextOptionName);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        /** @var DependencyInjectionFormAccessor $formAccessor */
        $formAccessor = $context->get($contextOptionName);
        $this->assertInstanceOf(DependencyInjectionFormAccessor::class, $formAccessor);

        $this->container->expects(self::once())
            ->method('get')
            ->with($serviceId);

        $formAccessor->getForm();
    }
}
