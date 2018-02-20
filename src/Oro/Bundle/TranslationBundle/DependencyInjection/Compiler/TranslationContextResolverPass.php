<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationContextResolverPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const EXTENSION_TAG = 'oro_translation.extension.translation_context_resolver';
    const SERVICE_ID = 'oro_translation.provider.translation_context';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::EXTENSION_TAG, 'addExtension');
    }
}
