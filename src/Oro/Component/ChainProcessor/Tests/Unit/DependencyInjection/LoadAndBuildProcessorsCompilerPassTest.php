<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\LoadAndBuildProcessorsCompilerPass;
use Oro\Component\ChainProcessor\ProcessorBagConfigProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LoadAndBuildProcessorsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWithoutProcessorBagConfigProvider()
    {
        $container = new ContainerBuilder();

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor1' => $processor1
        ]);

        $compilerPass = new LoadAndBuildProcessorsCompilerPass('processor_bag_config_provider', 'processor');

        $compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $groups = ['action1' => ['group1'], 'action2' => ['group2']];
        $processorBagConfigProvider = new Definition(ProcessorBagConfigProvider::class, [$groups, []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1', 'group' => 'group1', 'priority' => 123]);
        $processor1->addTag('processor', ['action' => 'action2', 'group' => 'group2', 'test_attr' => 'test']);

        $processor2 = new Definition('Test\Processor2');
        $processor2->addTag('processor', ['action' => 'action1']);

        $processor3 = new Definition('Test\Processor3');
        $processor3->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_provider' => $processorBagConfigProvider,
            'processor1'                    => $processor1,
            'processor2'                    => $processor2,
            'processor3'                    => $processor3
        ]);

        $compilerPass = new LoadAndBuildProcessorsCompilerPass('processor_bag_config_provider', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [
                'action1' => [
                    ['processor3', []],
                    ['processor2', []],
                    ['processor1', ['group' => 'group1']]
                ],
                'action2' => [
                    ['processor3', []],
                    ['processor1', ['group' => 'group2', 'test_attr' => 'test']]
                ]
            ],
            $processorBagConfigProvider->getArgument(1)
        );
    }

    public function testProcessForProcessorBagConfigProviderWithoutArguments()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigProvider = new Definition(ProcessorBagConfigProvider::class, []);

        $processor1 = new Definition('Test\Processor2');
        $processor1->addTag('processor', ['action' => 'action1']);

        $container->addDefinitions([
            'processor_bag_config_provider' => $processorBagConfigProvider,
            'processor1'                    => $processor1
        ]);

        $compilerPass = new LoadAndBuildProcessorsCompilerPass('processor_bag_config_provider', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [],
            $processorBagConfigProvider->getArgument(0)
        );
        self::assertEquals(
            ['action1' => [['processor1', []]]],
            $processorBagConfigProvider->getArgument(1)
        );
    }

    public function testProcessForProcessorBagConfigProviderWithoutProcessorsArgument()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $groups = ['action1' => ['group1']];
        $processorBagConfigProvider = new Definition(ProcessorBagConfigProvider::class, [$groups]);

        $processor1 = new Definition('Test\Processor2');
        $processor1->addTag('processor', ['action' => 'action1', 'group' => 'group1']);

        $container->addDefinitions([
            'processor_bag_config_provider' => $processorBagConfigProvider,
            'processor1'                    => $processor1
        ]);

        $compilerPass = new LoadAndBuildProcessorsCompilerPass('processor_bag_config_provider', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            $groups,
            $processorBagConfigProvider->getArgument(0)
        );
        self::assertEquals(
            ['action1' => [['processor1', ['group' => 'group1']]]],
            $processorBagConfigProvider->getArgument(1)
        );
    }

    public function testProcessForProcessorBagConfigProviderWhenGroupsArgumentIsParameter()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $groups = ['action1' => ['group1']];
        $container->setParameter('processor_groups', $groups);

        $processorBagConfigProvider = new Definition(ProcessorBagConfigProvider::class, ['%processor_groups%']);

        $processor1 = new Definition('Test\Processor2');
        $processor1->addTag('processor', ['action' => 'action1', 'group' => 'group1']);

        $container->addDefinitions([
            'processor_bag_config_provider' => $processorBagConfigProvider,
            'processor1'                    => $processor1
        ]);

        $compilerPass = new LoadAndBuildProcessorsCompilerPass('processor_bag_config_provider', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            '%processor_groups%',
            $processorBagConfigProvider->getArgument(0)
        );
        self::assertEquals(
            ['action1' => [['processor1', ['group' => 'group1']]]],
            $processorBagConfigProvider->getArgument(1)
        );
    }
}
