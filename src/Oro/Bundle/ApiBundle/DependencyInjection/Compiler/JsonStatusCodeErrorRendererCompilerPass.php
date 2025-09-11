<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures JSON status code error renderer.
 */
class JsonStatusCodeErrorRendererCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $jsonStatusCodeErrorRenderer = $container->getDefinition('oro_platform.fix_json_status_code_error_renderer');
        $jsonStatusCodeErrorRenderer->setArgument(1, array_merge(
            $jsonStatusCodeErrorRenderer->getArgument(1),
            ['application/vnd.api+json']
        ));
    }
}
