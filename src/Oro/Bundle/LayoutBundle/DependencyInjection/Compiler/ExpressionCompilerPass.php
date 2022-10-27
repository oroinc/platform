<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all expression encoders and language providers.
 */
class ExpressionCompilerPass implements CompilerPassInterface
{
    private const EXPRESSION_ENCODING_SERVICE_ID = 'oro_layout.expression.encoder_registry';
    private const EXPRESSION_ENCODER_TAG_NAME = 'layout.expression.encoder';

    private const EXPRESSION_LANGUAGE_SERVICE_ID = 'oro_layout.expression_language';
    private const EXPRESSION_LANGUAGE_PROVIDER_TAG_NAME = 'layout.expression_language_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExpressionEncoders($container);
        $this->loadExpressionLanguageProviders($container);
    }

    private function loadExpressionEncoders(ContainerBuilder $container): void
    {
        $serviceIds = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_ENCODER_TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            foreach ($attributes as $attr) {
                $serviceIds[$attr['format']] = new Reference($id);
            }
        }

        $container->getDefinition(self::EXPRESSION_ENCODING_SERVICE_ID)
            ->replaceArgument(0, $serviceIds);
    }

    private function loadExpressionLanguageProviders(ContainerBuilder $container): void
    {
        $providers = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_LANGUAGE_PROVIDER_TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            $providers[] = new Reference($id);
        }

        $container->getDefinition(self::EXPRESSION_LANGUAGE_SERVICE_ID)
            ->replaceArgument(1, $providers);
    }
}
