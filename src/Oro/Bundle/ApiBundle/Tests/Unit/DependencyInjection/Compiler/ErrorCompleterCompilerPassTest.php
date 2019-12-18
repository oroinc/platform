<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ErrorCompleterCompilerPass;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ErrorCompleterCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ErrorCompleterCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ErrorCompleterCompilerPass();

        $this->registry = $this->container->setDefinition(
            'oro_api.error_completer_registry',
            new Definition(ErrorCompleterRegistry::class, [[], null])
        );
    }

    public function testProcessWhenNoErrorCompleterCompilers()
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
        $errorCompleter1 = $this->container->setDefinition('error_completer1', new Definition());
        $errorCompleter1->addTag(
            'oro.api.error_completer',
            ['requestType' => 'rest']
        );
        $errorCompleter2 = $this->container->setDefinition('error_completer2', new Definition());
        $errorCompleter2->addTag(
            'oro.api.error_completer',
            ['priority' => -10]
        );
        $errorCompleter2->addTag(
            'oro.api.error_completer',
            ['requestType' => 'json_api', 'priority' => 10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['error_completer2', 'json_api'],
                ['error_completer1', 'rest'],
                ['error_completer2', null]
            ],
            $this->registry->getArgument(0)
        );

        $serviceLocatorReference = $this->registry->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'error_completer1' => new ServiceClosureArgument(new Reference('error_completer1')),
                'error_completer2' => new ServiceClosureArgument(new Reference('error_completer2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
