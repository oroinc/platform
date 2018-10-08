<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\EntityIdResolverCompilerPass;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EntityIdResolverCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityIdResolverCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new EntityIdResolverCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.entity_id_resolver_registry',
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
        $resolver1 = $this->container->setDefinition('resolver1', new Definition());
        $resolver1->addTag(
            'oro.api.entity_id_resolver',
            ['requestType' => 'rest', 'id' => 'id1', 'class' => 'Class1']
        );
        $resolver2 = $this->container->setDefinition('resolver2', new Definition());
        $resolver2->addTag(
            'oro.api.entity_id_resolver',
            ['priority' => -10, 'id' => 'id2', 'class' => 'Class1']
        );
        $resolver3 = $this->container->setDefinition('resolver3', new Definition());
        $resolver3->addTag(
            'oro.api.entity_id_resolver',
            ['requestType' => 'json_api', 'priority' => 10, 'id' => 'id1', 'class' => 'Class1']
        );
        $resolver4 = $this->container->setDefinition('resolver4', new Definition());
        $resolver4->addTag(
            'oro.api.entity_id_resolver',
            ['id' => 'id2', 'class' => 'Class2']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'id1' => [
                    'Class1' => [
                        ['resolver3', 'json_api'],
                        ['resolver1', 'rest']
                    ]
                ],
                'id2' => [
                    'Class1' => [
                        ['resolver2', null]
                    ],
                    'Class2' => [
                        ['resolver4', null]
                    ]
                ]
            ],
            $this->registry->getArgument(0)
        );
    }
}
