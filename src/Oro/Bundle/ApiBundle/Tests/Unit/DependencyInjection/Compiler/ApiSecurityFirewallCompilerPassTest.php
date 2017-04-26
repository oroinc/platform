<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ApiSecurityFirewallCompilerPass;
use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\Http\Firewall\ApiExceptionListener;
use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ApiSecurityFirewallCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApiSecurityFirewallCompilerPass */
    protected $compiler;

    /** @var ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ApiSecurityFirewallCompilerPass();
    }

    public function testProcessOnEmptySecurityConfig()
    {
        $this->container->prependExtensionConfig('security', []);

        $this->compiler->process($this->container);
        $this->assertEquals(['service_container'], $this->container->getServiceIds());
    }

    public function testProcessOnEmptySecurityFirewallsConfig()
    {
        $this->container->prependExtensionConfig('security', ['firewalls' => []]);

        $this->compiler->process($this->container);
        $this->assertEquals(['service_container'], $this->container->getServiceIds());
    }

    public function testProcessOnNonStatelessFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => false, 'context' => 'main']]]
        );
        $this->container->setParameter('session.storage.options', ['name' => 'test']);

        $this->compiler->process($this->container);
        $this->assertEquals(['service_container'], $this->container->getServiceIds());
    }

    public function testProcessOnStatelessButWithoutContextFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true]]]
        );
        $this->container->setParameter('session.storage.options', ['name' => 'test']);

        $this->compiler->process($this->container);
        $this->assertEquals(['service_container'], $this->container->getServiceIds());
    }

    public function testProcessOnStatelessButWithoutMapContext()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );
        $this->container->setParameter('session.storage.options', ['name' => 'test']);

        $this->compiler->process($this->container);
        $this->assertEquals(['service_container'], $this->container->getServiceIds());
    }

    public function testProcess()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );
        $exceptionListener = new Reference('exceptionListener');
        $exceptionListenerDefinition = new Definition(ExceptionListener::class, []);
        $this->container->setDefinition(
            'exceptionListener',
            $exceptionListenerDefinition
        );
        $contextFirewallContext = new Definition(
            FirewallContext::class,
            [
                [new Reference('security.access_listener')],
                $exceptionListener
            ]
        );

        $this->container->setDefinition(
            'security.firewall.map.context.testFirewall',
            $contextFirewallContext
        );
        $this->container->setParameter('session.storage.options', ['name' => 'test']);

        $this->compiler->process($this->container);


        $contextListener = $this->container->getDefinition('oro_security.context_listener.main');
        $this->assertEquals('security.context_listener', $contextListener->getParent());
        $this->assertEquals('main', $contextListener->getArgument(2));
        $contextFirewallListener = $this->container->getDefinition('oro_security.context_listener.main.testFirewall');
        $this->assertEquals(SecurityFirewallContextListener::class, $contextFirewallListener->getClass());
        $this->assertEquals('oro_security.context_listener.main', (string)$contextFirewallListener->getArgument(0));
        $this->assertEquals(ApiExceptionListener::class, $exceptionListenerDefinition->getClass());

        $listeners = $contextFirewallContext->getArgument(0);
        $this->assertCount(2, $listeners);
        // Context serializer listener should does before the access listener
        $this->assertEquals('oro_security.context_listener.main.testfirewall', (string)$listeners[0]);
        $this->assertEquals('security.access_listener', (string)$listeners[1]);
    }
}
