<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\PostProcessorCompilerPass;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class PostProcessorCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var PostProcessorCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new PostProcessorCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.post_processor_registry',
            new Definition(PostProcessorRegistry::class, [[], null])
        );
    }

    public function testProcessWhenNoPostProcessors()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));

        $serviceLocatorReference = $this->registry->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess()
    {
        $postProcessor1 = $this->container->setDefinition('post_processor1', new Definition());
        $postProcessor1->addTag(
            'oro.api.post_processor',
            ['alias' => 'alias1', 'requestType' => 'rest']
        );
        $postProcessor2 = $this->container->setDefinition('post_processor2', new Definition());
        $postProcessor2->addTag(
            'oro.api.post_processor',
            ['alias' => 'alias1', 'priority' => -10]
        );
        $postProcessor2->addTag(
            'oro.api.post_processor',
            ['alias' => 'alias2', 'priority' => -10]
        );
        $postProcessor2->addTag(
            'oro.api.post_processor',
            ['alias' => 'alias2', 'requestType' => 'rest', 'priority' => 10]
        );
        $postProcessor2->addTag(
            'oro.api.post_processor',
            ['alias' => 'alias2', 'requestType' => 'json_api']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'alias1' => [
                    ['post_processor1', 'rest'],
                    ['post_processor2', null]
                ],
                'alias2' => [
                    ['post_processor2', 'rest'],
                    ['post_processor2', 'json_api'],
                    ['post_processor2', null]
                ]
            ],
            $this->registry->getArgument(0)
        );

        $serviceLocatorReference = $this->registry->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'post_processor1' => new ServiceClosureArgument(new Reference('post_processor1')),
                'post_processor2' => new ServiceClosureArgument(new Reference('post_processor2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
