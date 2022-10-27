<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\GroupingWidgetProviderPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class GroupingWidgetProviderPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID = 'test_service';
    private const TAG_NAME   = 'test_tag';
    private const PAGE_TYPE  = 1;

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $service = $container->setDefinition(
            self::SERVICE_ID,
            new Definition(\stdClass::class, [[], null, null, null, null])
        );

        $container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 100]);
        $container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -100, 'group' => 'test']);
        $container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME);
        $container->setDefinition('tagged_service_4', new Definition())
            ->addTag(self::TAG_NAME, ['group' => 'test']);
        $container->setDefinition('tagged_service_5', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 100]);

        $compiler = new GroupingWidgetProviderPass(self::SERVICE_ID, self::TAG_NAME, self::PAGE_TYPE);
        $compiler->process($container);

        self::assertEquals(
            [
                ['tagged_service_2', 'test'],
                ['tagged_service_3', null],
                ['tagged_service_4', 'test'],
                ['tagged_service_1', null],
                ['tagged_service_5', null]
            ],
            $service->getArgument(0)
        );

        $serviceLocatorReference = $service->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'tagged_service_1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'tagged_service_2' => new ServiceClosureArgument(new Reference('tagged_service_2')),
                'tagged_service_3' => new ServiceClosureArgument(new Reference('tagged_service_3')),
                'tagged_service_4' => new ServiceClosureArgument(new Reference('tagged_service_4')),
                'tagged_service_5' => new ServiceClosureArgument(new Reference('tagged_service_5'))
            ],
            $serviceLocatorDef->getArgument(0)
        );

        self::assertEquals(self::PAGE_TYPE, $service->getArgument(4));
    }
}
