<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigExpressionCompilerPass implements CompilerPassInterface
{
    const EXPRESSION_TAG = 'oro_layout.expression';
    const EXTENSION_SERVICE = 'oro_layout.expression.extension';

    const EXPRESSION_ENCODER_TAG = 'oro_layout.expression.encoder';
    const EXPRESSION_ENCODING_SERVICE = 'oro_layout.block_type_extension.config_expression';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExpressions($container);
        $this->loadExpressionEncoders($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExpressions(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXTENSION_SERVICE)) {
            return;
        }

        $serviceIds       = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_TAG);
        foreach ($taggedServiceIds as $id => $attributes) {
            $container->getDefinition($id)->setScope(ContainerInterface::SCOPE_PROTOTYPE);

            foreach ($attributes as $attr) {
                $aliases = explode('|', $attr['alias']);
                foreach ($aliases as $alias) {
                    $serviceIds[$alias] = $id;
                }
            }
        }

        $extensionDef = $container->getDefinition(self::EXTENSION_SERVICE);
        $extensionDef->replaceArgument(1, $serviceIds);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExpressionEncoders(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXPRESSION_ENCODING_SERVICE)) {
            return;
        }

        $serviceIds       = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_ENCODER_TAG);
        foreach ($taggedServiceIds as $id => $attributes) {
            foreach ($attributes as $attr) {
                $serviceIds[$attr['format']] = $id;
            }
        }

        $encodingServiceDef = $container->getDefinition(self::EXPRESSION_ENCODING_SERVICE);
        $encodingServiceDef->replaceArgument(2, $serviceIds);
    }
}
