<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ErrorCompleterCompilerPass;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
            new Definition(ErrorCompleterRegistry::class, [[]])
        );
    }

    public function testProcessWhenNoErrorCompleterCompilers()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));
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
    }

    public function testProcessWhenErrorCompleterIsNotPublic()
    {
        $errorCompleter1 = $this->container->setDefinition('error_completer1', new Definition());
        $errorCompleter1->setPublic(false);
        $errorCompleter1->addTag(
            'oro.api.error_completer',
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['error_completer1', 'rest']
            ],
            $this->registry->getArgument(0)
        );
        self::assertTrue($errorCompleter1->isPublic());
    }
}
