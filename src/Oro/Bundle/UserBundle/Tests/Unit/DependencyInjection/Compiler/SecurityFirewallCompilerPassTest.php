<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFirewallCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecurityFirewallCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $providerDef;

    /** @var Definition */
    private $listenerDef;

    /** @var Definition */
    private $listener;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new SecurityFirewallCompilerPass();

        $this->providerDef = new Definition();
        $this->listenerDef = new Definition();
        $this->listener = new Definition();

        $this->container->setDefinition('escape_wsse_authentication.provider.test_firewall', $this->providerDef);
        $this->container->setDefinition('escape_wsse_authentication.listener.test_firewall', $this->listenerDef);
        $this->container->setDefinition('escape_wsse_authentication.listener', $this->listener);
    }

    public function testProcessAddedArgumentForListener()
    {
        $this->compiler->process($this->container);

        $arguments = $this->listener->getArguments();
        self::assertCount(1, $arguments);
        self::assertEquals(new Reference('oro_user.token.factory.wsse'), $arguments[0]);
    }

    public function testProcessOnEmptySecurityFirewallsConfig()
    {
        $this->container->prependExtensionConfig('security', ['firewalls' => []]);

        $this->compiler->process($this->container);

        self::assertEmpty($this->providerDef->getMethodCalls());
        self::assertEmpty($this->listenerDef->getMethodCalls());
    }

    public function testProcessOnFirewallWithoutWSSE()
    {
        $this->container->prependExtensionConfig(
            'security',
            [
                'firewalls' => [
                    'test_firewall' => [
                        'pattern' => 'test',
                        'security' => false
                    ]
                ]
            ]
        );

        $this->compiler->process($this->container);

        self::assertEmpty($this->providerDef->getMethodCalls());
        self::assertEmpty($this->listenerDef->getMethodCalls());
    }

    public function testProcessOnFirewallWithWSSE()
    {
        $this->container->prependExtensionConfig(
            'security',
            [
                'firewalls' => [
                    'test_firewall' => [
                        'pattern' => 'test',
                        'wsse' => [
                            'realm' => 'Secured API',
                            'profile' => 'UsernameToken'
                        ]
                    ]
                ]
            ]
        );

        $this->compiler->process($this->container);

        list($method, $parameters) = $this->providerDef->getMethodCalls()[0];
        $this->assertEquals('setFirewallName', $method);
        $this->assertEquals(['test_firewall'], $parameters);

        list($method, $parameters) = $this->listenerDef->getMethodCalls()[0];
        $this->assertEquals('setFirewallName', $method);
        $this->assertEquals(['test_firewall'], $parameters);
    }
}
