<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds all services tagged as `translation_adapter` to TranslationAdaptersCollection
 * It is not possible to use `!tagged` symfony shortcut because we need pass services aliases
 */
class TranslationAdaptersPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const EXTENSION_TAG = 'translation_adapter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            TranslationAdaptersCollection::class,
            self::EXTENSION_TAG,
            'addAdapter'
        );
    }
}
