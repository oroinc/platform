<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AccessRulesPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AccessRulesPassTest extends \PHPUnit\Framework\TestCase
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
        $serviceDefinition = $this->container->register('oro_security.access_rule_executor');

        $this->compilerPass->process($this->container);

        $this->assertSame([], $serviceDefinition->getArgument(0));
        $serviceLocatorReference = $serviceDefinition->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertSame([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess()
    {
        $serviceDefinition = $this->container->register('oro_security.access_rule_executor');

        $definition = $this->container->register('rule_should_be_last');
        $definition->addTag('oro_security.access_rule', ['priority' => -255]);

        $definition = $this->container->register('rule1');
        $definition->addTag('oro_security.access_rule', []);

        $definition = $this->container->register('rule2');
        $definition->addTag('oro_security.access_rule', []);

        $definition = $this->container->register('rule_should_be_first');
        $definition->addTag('oro_security.access_rule', ['priority' => 255]);

        $this->compilerPass->process($this->container);

        $this->assertSame(
            [
                'rule_should_be_first',
                'rule1',
                'rule2',
                'rule_should_be_last'
            ],
            $serviceDefinition->getArgument(0)
        );
        $serviceLocatorReference = $serviceDefinition->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'rule1'                => new ServiceClosureArgument(new Reference('rule1')),
                'rule2'                => new ServiceClosureArgument(new Reference('rule2')),
                'rule_should_be_first' => new ServiceClosureArgument(new Reference('rule_should_be_first')),
                'rule_should_be_last'  => new ServiceClosureArgument(new Reference('rule_should_be_last'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
