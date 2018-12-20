<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\RestRoutesCompilerPass;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RestRoutesCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestRoutesCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new RestRoutesCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.rest.routes_registry',
            new Definition(RestRoutesRegistry::class, [[]])
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
            'oro.api.rest_routes',
            ['requestType' => 'first&rest']
        );
        $errorCompleter1->addTag(
            'oro.api.rest_routes',
            ['requestType' => 'rest', 'priority' => -10]
        );
        $errorCompleter2 = $this->container->setDefinition('provider2', new Definition());
        $errorCompleter2->addTag(
            'oro.api.rest_routes',
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
