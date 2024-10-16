<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the context
 * to request an information about fields that can be used to sort a result.
 */
class SortersConfigExtra implements ConfigExtraSectionInterface
{
    public const NAME = ConfigUtil::SORTERS;

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
    public function getConfigType(): string
    {
        return ConfigUtil::SORTERS;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }
}
