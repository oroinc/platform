<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\Command\JsRoutingDumpCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Reconfigures the "fos_js_routing.dump_command" service.
 */
class JsRoutingPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('fos_js_routing.dump_command')
            ->setClass(JsRoutingDumpCommand::class)
            ->setPublic(false)
            ->setArguments([
                new Reference('fos_js_routing.extractor'),
                new Reference('fos_js_routing.serializer'),
                '%fos_js_routing.request_context_base_url%',
                '%oro_navigation.js_routing_filename_prefix%',
                new Reference('oro_navigation.file_manager.public_js')
            ]);
    }
}
