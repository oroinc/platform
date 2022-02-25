<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ContentProviderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();

        $manager = $container->setDefinition(
            'oro_ui.content_provider.manager',
            new Definition(ContentProviderManager::class, [[], null, []])
        );

        $container->setDefinition('tagged_service_1', new Definition())
            ->addTag('oro_ui.content_provider', ['alias' => 'provider1']);
        $container->setDefinition('tagged_service_2', new Definition())
            ->addTag('oro_ui.content_provider', ['alias' => 'provider2', 'enabled' => false]);
        $container->setDefinition('tagged_service_3', new Definition())
            ->addTag('oro_ui.content_provider', ['alias' => 'provider3']);

        $pass = new ContentProviderPass();
        $pass->process($container);

        self::assertEquals(['provider1', 'provider2', 'provider3'], $manager->getArgument('$providerNames'));

        $serviceLocatorReference = $manager->getArgument('$providerContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'provider1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'provider2' => new ServiceClosureArgument(new Reference('tagged_service_2')),
                'provider3' => new ServiceClosureArgument(new Reference('tagged_service_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );

        self::assertEquals(['provider1', 'provider3'], $manager->getArgument('$enabledProviderNames'));
    }
}
