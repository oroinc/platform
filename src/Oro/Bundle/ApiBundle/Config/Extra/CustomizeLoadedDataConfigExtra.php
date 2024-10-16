<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request customize loaded data handlers.
 * See details in documentations about "customize_loaded_data" action.
 */
class CustomizeLoadedDataConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'customize_loaded_data';

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureContext(ConfigContext $context): void
    {
        // no any modifications of the ConfigContext is required
    }

    #[\Override]
    public function isPropagable(): bool
    {
        return false;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }
}
