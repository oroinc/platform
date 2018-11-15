<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\FilterNamesCompilerPass;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FilterNamesCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterNamesCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new FilterNamesCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.filter_names_registry',
            new Definition(FilterNamesRegistry::class, [[]])
        );
    }

    public function testProcessWhenNoRoutesProviders()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));
    }

    public function testProcess()
    {
        $errorCompleter1 = $this->container->setDefinition('provider1', new Definition());
        $errorCompleter1->addTag(
            'oro.api.filter_names',
            ['requestType' => 'first&rest']
        );
        $errorCompleter1->addTag(
            'oro.api.filter_names',
            ['requestType' => 'rest', 'priority' => -10]
        );
        $errorCompleter2 = $this->container->setDefinition('provider2', new Definition());
        $errorCompleter2->addTag(
            'oro.api.filter_names',
            ['requestType' => 'second&rest']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                [new Reference('provider1'), 'first&rest'],
                [new Reference('provider2'), 'second&rest'],
                [new Reference('provider1'), 'rest']
            ],
            $this->registry->getArgument(0)
        );
    }
}
