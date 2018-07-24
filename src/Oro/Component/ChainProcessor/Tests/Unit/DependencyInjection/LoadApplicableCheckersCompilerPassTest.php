<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LoadApplicableCheckersCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWithoutProcessorBag()
    {
        $container = new ContainerBuilder();

        $applicableChecker1 = new Definition('Test\ApplicableChecker1');
        $applicableChecker1->addTag('applicable_checker');

        $container->addDefinitions([
            'applicable_checker1' => $applicableChecker1
        ]);

        $compilerPass = new LoadApplicableCheckersCompilerPass('processor_bag', 'applicable_checker');

        $compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $processorBag = new Definition();

        $applicableChecker1 = new Definition('Test\ApplicableChecker1');
        $applicableChecker1->addTag('applicable_checker');

        $applicableChecker2 = new Definition('Test\ApplicableChecker1');
        $applicableChecker2->addTag('applicable_checker', ['priority' => 123]);

        $container->addDefinitions([
            'processor_bag'       => $processorBag,
            'applicable_checker1' => $applicableChecker1,
            'applicable_checker2' => $applicableChecker2
        ]);

        $compilerPass = new LoadApplicableCheckersCompilerPass('processor_bag', 'applicable_checker');

        $compilerPass->process($container);

        $methodCalls = $processorBag->getMethodCalls();
        self::assertCount(2, $methodCalls);
        self::assertEquals(
            [
                'addApplicableChecker',
                [new Reference('applicable_checker1'), 0]
            ],
            $methodCalls[0]
        );
        self::assertEquals(
            [
                'addApplicableChecker',
                [new Reference('applicable_checker2'), 123]
            ],
            $methodCalls[1]
        );
    }
}
