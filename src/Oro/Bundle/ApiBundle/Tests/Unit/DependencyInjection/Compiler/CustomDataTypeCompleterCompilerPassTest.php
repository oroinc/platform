<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\CustomDataTypeCompleterCompilerPass;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteCustomDataTypeHelper;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CustomDataTypeCompleterCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomDataTypeCompleterCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $completerHelper;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new CustomDataTypeCompleterCompilerPass();

        $this->completerHelper = $this->container->setDefinition(
            'oro_api.complete_definition_helper.custom_data_type',
            new Definition(CompleteCustomDataTypeHelper::class, [[], null])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage At least one service tagged by "oro.api.custom_data_type_completer" must be registered.
     */
    public function testProcessWhenNoCompleters()
    {
        $this->compiler->process($this->container);
    }

    public function testProcess()
    {
        $completer1 = $this->container->setDefinition('completer1', new Definition());
        $completer1->addTag(
            'oro.api.custom_data_type_completer',
            ['priority' => -255]
        );
        $completer2 = $this->container->setDefinition('completer2', new Definition());
        $completer2->addTag(
            'oro.api.custom_data_type_completer'
        );
        $completer3 = $this->container->setDefinition('completer3', new Definition());
        $completer3->addTag(
            'oro.api.custom_data_type_completer',
            ['requestType' => 'rest', 'priority' => 10]
        );
        $completer3->addTag(
            'oro.api.custom_data_type_completer',
            ['requestType' => 'json_api', 'priority' => -10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                [new Reference('completer3'), 'rest'],
                [new Reference('completer2'), null],
                [new Reference('completer3'), 'json_api'],
                [new Reference('completer1'), null]
            ],
            $this->completerHelper->getArgument(0)
        );

        $serviceLocatorReference = $this->completerHelper->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'completer1' => new ServiceClosureArgument(new Reference('completer1')),
                'completer2' => new ServiceClosureArgument(new Reference('completer2')),
                'completer3' => new ServiceClosureArgument(new Reference('completer3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
