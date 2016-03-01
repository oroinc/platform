<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request inclusion of additional related entities data
 */
class ExpandRelatedEntitiesConfigExtra implements ConfigExtraInterface
{
    const NAME = 'expand_related_entities';

    /** @var array */
    protected $expandedEntities;

    /**
     * @param array $expandedEntities
     */
    public function __construct($expandedEntities = [])
    {
        $this->expandedEntities = $expandedEntities;
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
        $context->set(self::NAME, $this->expandedEntities);
    }
}
