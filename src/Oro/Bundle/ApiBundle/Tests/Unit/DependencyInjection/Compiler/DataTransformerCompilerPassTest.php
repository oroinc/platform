<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DataTransformerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DataTransformerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataTransformerCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new DataTransformerCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.data_transformer_registry',
            new Definition(DataTransformerRegistry::class, [[]])
        );
    }

    public function testProcessWhenNoDataTransformers()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));
    }

    public function testProcess()
    {
        $transformer1 = $this->container->setDefinition('transformer1', new Definition());
        $transformer1->addTag(
            'oro.api.data_transformer',
            ['dataType' => 'type1', 'requestType' => 'rest']
        );
        $transformer2 = $this->container->setDefinition('transformer2', new Definition());
        $transformer2->addTag(
            'oro.api.data_transformer',
            ['dataType' => 'type1', 'priority' => -10]
        );
        $transformer2->addTag(
            'oro.api.data_transformer',
            ['dataType' => 'type2', 'priority' => -10]
        );
        $transformer2->addTag(
            'oro.api.data_transformer',
            ['dataType' => 'type2', 'requestType' => 'rest', 'priority' => 10]
        );
        $transformer2->addTag(
            'oro.api.data_transformer',
            ['dataType' => 'type2', 'requestType' => 'json_api']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'type1' => [
                    [new Reference('transformer1'), 'rest'],
                    [new Reference('transformer2'), null]
                ],
                'type2' => [
                    [new Reference('transformer2'), 'rest'],
                    [new Reference('transformer2'), 'json_api'],
                    [new Reference('transformer2'), null]
                ]
            ],
            $this->registry->getArgument(0)
        );
    }
}
