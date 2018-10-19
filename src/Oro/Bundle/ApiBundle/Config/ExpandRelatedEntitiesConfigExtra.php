<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add related entities to a result.
 */
class ExpandRelatedEntitiesConfigExtra implements ConfigExtraInterface
{
    const NAME = 'expand_related_entities';

    /** @var string[] */
    protected $expandedEntities;

    /**
     * @param string[] $expandedEntities The list of related entities.
     *                                   Each item can be an association name or a path to a nested association.
     *                                   Example: ["comments", "comments.author"]
     *                                   Where "comments" is an association under a primary entity,
     *                                   "author" is an association under the "comments" entity.
     */
    public function __construct(array $expandedEntities)
    {
        $this->expandedEntities = $expandedEntities;
    }

    /**
     * Gets the list of related entities.
     * Each item can be an association name or a path to a nested association.
     * Example: ["comments", "comments.author"]
     * Where "comments" is an association under a primary entity,
     * "author" is an association under the "comments" entity.
     *
     * @return string[]
     */
    public function getExpandedEntities()
    {
        return $this->expandedEntities;
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
        return 'expand:' . implode(',', $this->expandedEntities);
    }
}
