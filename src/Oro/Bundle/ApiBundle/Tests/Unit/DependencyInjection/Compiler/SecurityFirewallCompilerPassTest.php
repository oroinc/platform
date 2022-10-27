<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Oro\Bundle\ApiBundle\Security\FeatureDependedFirewallMap;
use Oro\Bundle\ApiBundle\Security\Http\Firewall\ContextListener;
use Oro\Bundle\ApiBundle\Security\Http\Firewall\ExceptionListener;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener as BaseExceptionListener;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFirewallCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecurityFirewallCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new SecurityFirewallCompilerPass();

        $this->container->register('security.firewall.map', FirewallMap::class)
            ->addArgument(new Reference('service_container'))
            ->addArgument([]);
        DependencyInjectionUtil::setConfig(
            $this->container,
            [
                'api_firewalls' => [
                    'testFirewall' => [
                        'feature_name' => 'web_api'
                    ]
                ]
            ]
        );
    }

    private function assertFirewallMap()
    {
        $firewallMap = $this->container->getDefinition('security.firewall.map');
        self::assertEquals(FeatureDependedFirewallMap::class, $firewallMap->getClass());
        self::assertEquals(
            new Reference('oro_featuretoggle.checker.feature_checker'),
            $firewallMap->getArgument(2)
        );
        self::assertEquals(
            new Reference('oro_api.security.firewall.feature_access_listener'),
            $firewallMap->getArgument(3)
        );
        self::assertEquals(
            [
                'testFirewall' => [
                    'feature_name' => 'web_api'
                ]
            ],
            $firewallMap->getArgument(4)
        );
    }

    public function testProcessOnEmptySecurityConfig()
    {
        $this->container->prependExtensionConfig('security', []);

        $this->compiler->process($this->container);
        $this->assertFirewallMap();
    }

    public function testProcessOnEmptySecurityFirewallsConfig()
    {
        $this->container->prependExtensionConfig('security', ['firewalls' => []]);

        $this->compiler->process($this->container);
        $this->assertFirewallMap();
    }

    public function testProcessOnNonStatelessFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => false, 'context' => 'main']]]
        );

        $this->compiler->process($this->container);
        $this->assertFirewallMap();
    }

    public function testProcessOnStatelessButWithoutContextFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true]]]
        );

        $this->compiler->process($this->container);
        $this->assertFirewallMap();
    }

    public function testProcessOnStatelessButWithoutMapContext()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );

        $this->compiler->process($this->container);
        $this->assertFirewallMap();
    }

    public function testProcess()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );
        $exceptionListener = new Reference('exceptionListener');
        $exceptionListenerDefinition = new Definition(BaseExceptionListener::class, []);
        $this->container->setDefinition(
            'exceptionListener',
            $exceptionListenerDefinition
        );
        $contextFirewallContext = new Definition(
            FirewallContext::class,
            [
                new IteratorArgument([new Reference('security.access_listener')]),
                $exceptionListener
            ]
        );

        $this->container->setDefinition(
            'security.firewall.map.context.testFirewall',
            $contextFirewallContext
        );

        $this->compiler->process($this->container);

        $this->assertFirewallMap();

        $contextListener = $this->container->getDefinition('oro_security.context_listener.main');
        self::assertEquals('security.context_listener', $contextListener->getParent());
        self::assertEquals('main', $contextListener->getArgument(2));
        $contextFirewallListener = $this->container->getDefinition('oro_security.context_listener.main.testFirewall');
        self::assertEquals(ContextListener::class, $contextFirewallListener->getClass());
        self::assertEquals(
            [
                new Reference('oro_security.context_listener.main'),
                new Reference('security.token_storage')
            ],
            $contextFirewallListener->getArguments()
        );
        self::assertTrue($contextFirewallListener->hasMethodCall('setCsrfRequestManager'));
        self::assertTrue($contextFirewallListener->hasMethodCall('setCsrfProtectedRequestHelper'));
        self::assertEquals(ExceptionListener::class, $exceptionListenerDefinition->getClass());

        $listeners = $contextFirewallContext->getArgument(0)->getValues();
        self::assertCount(2, $listeners);
        // the context listener should be before the access listener
        self::assertEquals('oro_security.context_listener.main.testFirewall', (string)$listeners[0]);
        self::assertEquals('security.access_listener', (string)$listeners[1]);
    }

    public function testProcessWithRememberMeListener()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => [
                'testFirewall' => ['stateless' => true, 'context' => 'main', 'organization-remember-me' =>[]]
            ]]
        );
        $exceptionListener = new Reference('exceptionListener');
        $exceptionListenerDefinition = new Definition(BaseExceptionListener::class, []);
        $this->container->setDefinition('exceptionListener', $exceptionListenerDefinition);

        $contextFirewallContext = new Definition(
            FirewallContext::class,
            [
                new IteratorArgument([
                    new Reference('oro_security.authentication.listener.rememberme.main'),
                    new Reference('security.access_listener')
                ]),
                $exceptionListener
            ]
        );

        $this->container->setDefinition(
            'security.firewall.map.context.testFirewall',
            $contextFirewallContext
        );

        $this->compiler->process($this->container);

        $this->assertFirewallMap();

        $listeners = $contextFirewallContext->getArgument(0)->getValues();
        self::assertCount(3, $listeners);
        // the context listener should be before the access listener or remember me listener
        self::assertEquals('oro_security.context_listener.main.testFirewall', (string)$listeners[0]);
        self::assertEquals('oro_security.authentication.listener.rememberme.main', (string)$listeners[1]);
        self::assertEquals('security.access_listener', (string)$listeners[2]);
    }
}
