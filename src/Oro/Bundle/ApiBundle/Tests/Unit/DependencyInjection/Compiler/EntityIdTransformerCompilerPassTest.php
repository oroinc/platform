<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\EntityIdTransformerCompilerPass;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EntityIdTransformerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityIdTransformerCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new EntityIdTransformerCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.entity_id_transformer_registry',
            new Definition(EntityIdTransformerRegistry::class, [[]])
        );
    }

    public function testProcessWhenNoEntityIdTransformers()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));
    }

    public function testProcess()
    {
        $transformer1 = $this->container->setDefinition('transformer1', new Definition());
        $transformer1->addTag(
            'oro.api.entity_id_transformer',
            ['requestType' => 'rest']
        );
        $transformer2 = $this->container->setDefinition('transformer2', new Definition());
        $transformer2->addTag(
            'oro.api.entity_id_transformer',
            ['priority' => -10]
        );
        $transformer2->addTag(
            'oro.api.entity_id_transformer',
            ['requestType' => 'json_api', 'priority' => 10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['transformer2', 'json_api'],
                ['transformer1', 'rest'],
                ['transformer2', null]
            ],
            $this->registry->getArgument(0)
        );
    }
}
