<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request configuration of disabled associations.
 * @see \Oro\Bundle\ApiBundle\Provider\ResourcesProvider::isResourceEnabled
 */
class DisabledAssociationsConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'disabled_associations';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureContext(ConfigContext $context): void
    {
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagable(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }
}
