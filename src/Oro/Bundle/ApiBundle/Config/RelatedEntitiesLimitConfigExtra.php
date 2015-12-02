<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to set the maximum number of related entities that can be loaded.
 */
class RelatedEntitiesLimitConfigExtra implements ConfigExtraInterface
{
    const NAME = 'related_entities_limit';

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
}
