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

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context): void
    {
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigType(): string
    {
        return ConfigUtil::SORTERS;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }
}
