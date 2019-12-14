<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ObjectNormalizerCompilerPass;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ObjectNormalizerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectNormalizerCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ObjectNormalizerCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.object_normalizer_registry',
            new Definition(ObjectNormalizerRegistry::class, [[], null])
        );
    }

    public function testProcessWhenNoObjectNormalizers()
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
        $normalizer1 = $this->container->setDefinition('normalizer1', new Definition());
        $normalizer1->addTag(
            'oro.api.object_normalizer',
            ['class' => 'Class1', 'requestType' => 'rest']
        );
        $normalizer2 = $this->container->setDefinition('normalizer2', new Definition());
        $normalizer2->addTag(
            'oro.api.object_normalizer',
            ['class' => 'Class2', 'priority' => -10]
        );
        $normalizer2->addTag(
            'oro.api.object_normalizer',
            ['class' => 'Class2', 'requestType' => 'rest', 'priority' => 10]
        );
        $normalizer2->addTag(
            'oro.api.object_normalizer',
            ['class' => 'Class2', 'requestType' => 'json_api']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['normalizer2', 'Class2', 'rest'],
                ['normalizer1', 'Class1', 'rest'],
                ['normalizer2', 'Class2', 'json_api'],
                ['normalizer2', 'Class2', null]
            ],
            $this->registry->getArgument(0)
        );

        $serviceLocatorReference = $this->registry->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'normalizer1' => new ServiceClosureArgument(new Reference('normalizer1')),
                'normalizer2' => new ServiceClosureArgument(new Reference('normalizer2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
