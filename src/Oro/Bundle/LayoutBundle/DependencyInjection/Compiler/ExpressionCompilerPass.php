<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExpressionCompilerPass implements CompilerPassInterface
{
    const EXPRESSION_ENCODER_TAG = 'oro_layout.expression.encoder';
    const EXPRESSION_ENCODING_SERVICE = 'oro_layout.expression.encoder_registry';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExpressionEncoders($container);
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
        $encodingServiceDef->replaceArgument(1, $serviceIds);
    }
}
