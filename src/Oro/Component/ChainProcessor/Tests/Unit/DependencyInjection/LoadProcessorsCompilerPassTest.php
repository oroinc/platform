<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LoadProcessorsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWithoutProcessorBagConfigBuilder()
    {
        $container = new ContainerBuilder();

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor1' => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);
    }

    public function testCommonProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [[], []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [
                '' => [
                    0 => [['processor1', []]],
                ]
            ],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    public function testUngroupedProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [[], []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1']);

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [
                'action1' => [
                    0 => [['processor1', []]],
                ]
            ],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    public function testProcessorsWithScalarAttributes()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [[], []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1', 'group' => 'group1', 'priority' => 123]);
        $processor1->addTag('processor', ['action' => 'action2', 'group' => 'group2', 'test_attr' => 'test']);

        $processor2 = new Definition('Test\Processor2');
        $processor2->addTag('processor', ['action' => 'action1']);

        $processor3 = new Definition('Test\Processor3');
        $processor3->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1,
            'processor2'                   => $processor2,
            'processor3'                   => $processor3
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [
                ''        => [
                    0 => [['processor3', []]]
                ],
                'action1' => [
                    0   => [['processor2', []]],
                    123 => [['processor1', ['group' => 'group1']]]
                ],
                'action2' => [
                    0 => [['processor1', ['group' => 'group2', 'test_attr' => 'test']]]
                ],
            ],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    public function testProcessorWithComplexConditions()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [[], []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1', 'test_attr' => '!test1']);
        $processor1->addTag('processor', ['action' => 'action2', 'test_attr' => 'test1&test2']);
        $processor1->addTag('processor', ['action' => 'action3', 'test_attr' => 'test1|test2']);
        $processor1->addTag('processor', ['action' => 'action4', 'test_attr' => '!test1|test2']);
        $processor1->addTag('processor', ['action' => 'action5', 'test_attr' => 'test1|!test2']);

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [
                'action1' => [
                    0 => [
                        ['processor1', ['test_attr' => ['!' => 'test1']]]
                    ]
                ],
                'action2' => [
                    0 => [
                        ['processor1', ['test_attr' => ['&' => ['test1', 'test2']]]]
                    ]
                ],
                'action3' => [
                    0 => [
                        ['processor1', ['test_attr' => ['|' => ['test1', 'test2']]]]
                    ]
                ],
                'action4' => [
                    0 => [
                        ['processor1', ['test_attr' => ['|' => [['!' => 'test1'], 'test2']]]]
                    ]
                ],
                'action5' => [
                    0 => [
                        ['processor1', ['test_attr' => ['|' => ['test1', ['!' => 'test2']]]]]
                    ]
                ]
            ],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Tag attribute "group" can be used only if the attribute "action" is specified. Service: "processor1".
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWithInvalidConfigurationOfCommonProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [[], []]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['group' => 'group1']);

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);
    }

    public function testProcessForProcessorBagConfigBuilderWithoutArguments()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, []);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            [],
            $processorBagConfigBuilder->getArgument(0)
        );
        self::assertEquals(
            ['' => [0 => [['processor1', []]]]],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    public function testProcessForProcessorBagConfigBuilderWithoutProcessorsArgument()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $groups = ['action1' => ['group1' => 1]];
        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [$groups]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            $groups,
            $processorBagConfigBuilder->getArgument(0)
        );
        self::assertEquals(
            ['' => [0 => [['processor1', []]]]],
            $processorBagConfigBuilder->getArgument(1)
        );
    }

    public function testProcessForNotPublicProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $groups = ['action1' => ['group1' => 1]];
        $processorBagConfigBuilder = new Definition(ProcessorBagConfigBuilder::class, [$groups]);

        $processor1 = new Definition('Test\Processor1');
        $processor1->setPublic(false);
        $processor1->addTag('processor');

        $container->addDefinitions([
            'processor_bag_config_builder' => $processorBagConfigBuilder,
            'processor1'                   => $processor1
        ]);

        $compilerPass = new LoadProcessorsCompilerPass('processor_bag_config_builder', 'processor');

        $compilerPass->process($container);

        self::assertEquals(
            $groups,
            $processorBagConfigBuilder->getArgument(0)
        );
        self::assertEquals(
            ['' => [0 => [['processor1', []]]]],
            $processorBagConfigBuilder->getArgument(1)
        );
        self::assertTrue($processor1->isPublic());
    }
}
