<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add only identifier fields to a result.
 */
class FilterIdentifierFieldsConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'identifier_fields_only';

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
