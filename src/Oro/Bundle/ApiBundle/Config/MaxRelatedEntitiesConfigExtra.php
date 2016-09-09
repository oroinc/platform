<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to set the maximum number of related entities that can be loaded.
 */
class MaxRelatedEntitiesConfigExtra implements ConfigExtraInterface
{
    const NAME = 'max_related_entities';

    /** @var int */
    protected $maxRelatedEntities;

    /**
     * @param int $maxRelatedEntities
     */
    public function __construct($maxRelatedEntities)
    {
        $this->maxRelatedEntities = $maxRelatedEntities;
    }

    /**
     * Gets the maximum number of related entities that can be retrieved
     *
     * @return int
     */
    public function getMaxRelatedEntities()
    {
        return $this->maxRelatedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        $context->setMaxRelatedEntities($this->maxRelatedEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME . ':' . (string)$this->maxRelatedEntities;
    }
}
