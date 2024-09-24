<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request field value data transformers.
 */
class DataTransformersConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'data_transformers';

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
        return true;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }
}
