<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to set the maximum number of related entities that can be loaded.
 */
class MaxRelatedEntitiesConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'max_related_entities';

    private int $maxRelatedEntities;

    public function __construct(int $maxRelatedEntities)
    {
        $this->maxRelatedEntities = $maxRelatedEntities;
    }

    /**
     * Gets the maximum number of related entities that can be retrieved
     */
    public function getMaxRelatedEntities(): int
    {
        return $this->maxRelatedEntities;
    }

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
        $context->setMaxRelatedEntities($this->maxRelatedEntities);
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
    public function getCacheKeyPart(): ?string
    {
        return self::NAME . ':' . (string)$this->maxRelatedEntities;
    }
}
