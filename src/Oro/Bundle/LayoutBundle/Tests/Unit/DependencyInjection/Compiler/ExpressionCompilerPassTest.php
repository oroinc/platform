<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private ExpressionCompilerPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ExpressionCompilerPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $encoderRegistryDef = $container->register('oro_layout.expression.encoder_registry')
            ->addArgument([]);
        $expressionLanguageDef = $container->register('oro_layout.expression_language')
            ->setArguments([null, []]);

        $container->register('json_encoder')
            ->addTag('layout.expression.encoder', ['format' => 'json']);
        $container->register('xml_encoder')
            ->addTag('layout.expression.encoder', ['format' => 'xml']);

        $container->register('provider1')
            ->addTag('layout.expression_language_provider');
        $container->register('provider2')
            ->addTag('layout.expression_language_provider');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'json' => new Reference('json_encoder'),
                'xml'  => new Reference('xml_encoder')
            ],
            $encoderRegistryDef->getArgument(0)
        );
        $this->assertEquals(
            [
                new Reference('provider1'),
                new Reference('provider2')
            ],
            $expressionLanguageDef->getArgument(1)
        );
    }
}
