<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\EmbeddedFormBundle\Layout\Extension\DependencyInjectionFormContextConfigurator;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyInjectionFormContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var DependencyInjectionFormContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->contextConfigurator = new DependencyInjectionFormContextConfigurator($this->container);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage formServiceId should be specified.
     */
    public function testConfigureContextWithoutForm()
    {
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

        $formAccessor = $context->get($contextOptionName);
        $this->assertInstanceOf(DependencyInjectionFormAccessor::class, $formAccessor);
        $this->assertAttributeEquals($serviceId, 'formServiceId', $formAccessor);
    }
}
