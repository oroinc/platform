<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass;

class LoadProcessorsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessWithoutProcessorBag()
    {
        $container = new ContainerBuilder();

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor');

        $container->addDefinitions(
            [
                'processor1' => $processor1,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);
    }

    public function testApplicableCheckers()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition('Test\ProcessorBag');

        $applicableChecker1 = new Definition('Test\ApplicableChecker1');
        $applicableChecker1->addTag('applicable_checker');

        $applicableChecker2 = new Definition('Test\ApplicableChecker1');
        $applicableChecker2->addTag('applicable_checker', ['priority' => 123]);

        $container->addDefinitions(
            [
                'processor_bag'       => $processorBag,
                'applicable_checker1' => $applicableChecker1,
                'applicable_checker2' => $applicableChecker2,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        $this->assertCount(2, $methodCalls);
        $this->assertEquals(
            [
                'addApplicableChecker',
                [
                    new Reference('applicable_checker1'),
                    0
                ]
            ],
            $methodCalls[0]
        );
        $this->assertEquals(
            [
                'addApplicableChecker',
                [
                    new Reference('applicable_checker2'),
                    123
                ]
            ],
            $methodCalls[1]
        );
    }

    public function testCommonProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition('Test\ProcessorBag');

        $processor1 = new Definition('Test\Processor2');
        $processor1->addTag('processor');

        $container->addDefinitions(
            [
                'processor_bag' => $processorBag,
                'processor1'    => $processor1,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', [], null, null, 0]
            ],
            $methodCalls[0]
        );
    }

    public function testUngroupedProcessor()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition('Test\ProcessorBag');

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1']);

        $container->addDefinitions(
            [
                'processor_bag' => $processorBag,
                'processor1'    => $processor1,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', [], 'action1', null, 0]
            ],
            $methodCalls[0]
        );
    }

    public function testProcessorsWithScalarAttributes()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition('Test\ProcessorBag');

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1', 'group' => 'group1', 'priority' => 123]);
        $processor1->addTag('processor', ['action' => 'action2', 'group' => 'group2', 'test_attr' => 'test']);

        $processor2 = new Definition('Test\Processor2');
        $processor2->addTag('processor', ['action' => 'action1']);

        $processor3 = new Definition('Test\Processor3');
        $processor3->addTag('processor');

        $container->addDefinitions(
            [
                'processor_bag' => $processorBag,
                'processor1'    => $processor1,
                'processor2'    => $processor2,
                'processor3'    => $processor3,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        $this->assertCount(4, $methodCalls);
        $this->assertEquals(
            [
                'addProcessor',
                [
                    'processor1',
                    [],
                    'action1',
                    'group1',
                    123,
                ]
            ],
            $methodCalls[0]
        );
        $this->assertEquals(
            [
                'addProcessor',
                [
                    'processor1',
                    ['test_attr' => 'test'],
                    'action2',
                    'group2',
                    0,
                ]
            ],
            $methodCalls[1]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor2', [], 'action1', null, 0]
            ],
            $methodCalls[2]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor3', [], null, null, 0]
            ],
            $methodCalls[3]
        );
    }

    public function testProcessorWithComplexConditions()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition('Test\ProcessorBag');

        $processor1 = new Definition('Test\Processor1');
        $processor1->addTag('processor', ['action' => 'action1', 'test_attr' => '!test1']);
        $processor1->addTag('processor', ['action' => 'action2', 'test_attr' => 'test1&test2']);
        $processor1->addTag('processor', ['action' => 'action3', 'test_attr' => 'test1|test2']);
        $processor1->addTag('processor', ['action' => 'action4', 'test_attr' => '!test1|test2']);
        $processor1->addTag('processor', ['action' => 'action5', 'test_attr' => 'test1|!test2']);

        $container->addDefinitions(
            [
                'processor_bag' => $processorBag,
                'processor1'    => $processor1,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        $this->assertCount(5, $methodCalls);
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', ['test_attr' => ['!' => 'test1']], 'action1', null, 0]
            ],
            $methodCalls[0]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', ['test_attr' => ['&' => ['test1', 'test2']]], 'action2', null, 0]
            ],
            $methodCalls[1]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', ['test_attr' => ['|' => ['test1', 'test2']]], 'action3', null, 0]
            ],
            $methodCalls[2]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', ['test_attr' => ['|' => [['!' => 'test1'], 'test2']]], 'action4', null, 0]
            ],
            $methodCalls[3]
        );
        $this->assertEquals(
            [
                'addProcessor',
                ['processor1', ['test_attr' => ['|' => ['test1', ['!' => 'test2']]]], 'action5', null, 0]
            ],
            $methodCalls[4]
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

        $processorBag = new Definition('Test\ProcessorBag');

        $processor1 = new Definition('Test\Processor2');
        $processor1->addTag('processor', ['group' => 'group1']);

        $container->addDefinitions(
            [
                'processor_bag' => $processorBag,
                'processor1'    => $processor1,
            ]
        );

        $compilerPass = new LoadProcessorsCompilerPass(
            'processor_bag',
            'processor',
            'applicable_checker'
        );

        $compilerPass->process($container);
    }
}
