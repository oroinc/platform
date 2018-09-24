<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers preferred language providers in chain provider.
 */
class PreferredLanguageProviderPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const TAG = 'oro_locale.preferred_language_provider';
    const CHAIN_PROVIDER_ID = 'oro_locale.provider.chain_preferred_language_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::CHAIN_PROVIDER_ID, self::TAG, 'addProvider');
    }
}
