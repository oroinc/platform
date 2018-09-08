<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AccessRulesPass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AccessRulesPassTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var AccessRulesPass */
    private $compilerPass;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new AccessRulesPass();
    }

    public function testProcessWithEmptyRules()
    {
        $serviceDefinition = new Definition();
        $this->container->set('oro_security.access_rule.chain_access_rule', $serviceDefinition);

        $this->compilerPass->process($this->container);

        $this->assertEmpty($serviceDefinition->getMethodCalls());
    }

    public function testProcess()
    {
        $serviceDefinition = $this->container->register('oro_security.access_rule.chain_access_rule');

        $definition = $this->container->register('rule_should_be_last');
        $definition->addTag('oro_security.access_rule', ['priority' => -255]);

        $definition = $this->container->register('rule1');
        $definition->addTag('oro_security.access_rule', []);

        $definition = $this->container->register('rule2');
        $definition->addTag('oro_security.access_rule', []);

        $definition = $this->container->register('rule_should_be_first');
        $definition->addTag('oro_security.access_rule', ['priority' => 255]);

        $this->compilerPass->process($this->container);

        $this->assertEquals(
            [
                ['addRule', [new Reference('rule_should_be_first')]],
                ['addRule', [new Reference('rule1')]],
                ['addRule', [new Reference('rule2')]],
                ['addRule', [new Reference('rule_should_be_last')]]
            ],
            $serviceDefinition->getMethodCalls()
        );
    }
}
