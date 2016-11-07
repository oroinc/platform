<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\EmbeddedFormBundle\Layout\Extension\DependencyInjectionFormContextConfigurator;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor;

class DependencyInjectionFormContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var DependencyInjectionFormContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

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
