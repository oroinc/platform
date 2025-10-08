<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\JsonStatusCodeErrorRendererCompilerPass;
use Oro\Bundle\PlatformBundle\ErrorRenderer\FixJsonStatusCodeErrorRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class JsonStatusCodeErrorRendererCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $jsonStatusCodeErrorRenderer = $container->setDefinition(
            'oro_platform.fix_json_status_code_error_renderer',
            new Definition(FixJsonStatusCodeErrorRenderer::class, [null, ['application/json']])
        );

        $compiler = new JsonStatusCodeErrorRendererCompilerPass();
        $compiler->process($container);

        self::assertEquals(
            ['application/json', 'application/vnd.api+json'],
            $jsonStatusCodeErrorRenderer->getArgument(1)
        );
    }
}
