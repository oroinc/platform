<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

/**
 * This alias provider excludes aliases generation for test entities to avoid duplications of aliases.
 * TestActivity and TestActivityTarget entities does not covered by this class because they should have aliases
 * to build correct association API routes.
 */
class EntityAliasProvider implements EntityAliasProviderInterface
{
    protected static $classes = [
        'Oro\Bundle\TestFrameworkBundle\Entity\Item2',
        'Oro\Bundle\TestFrameworkBundle\Entity\ItemValue',
        'Oro\Bundle\TestFrameworkBundle\Entity\Product',
        'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
    ];

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (in_array($entityClass, self::$classes)) {
            return false;
        }

        return null;
    }
}
