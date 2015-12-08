<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class EntityAliasProvider implements EntityAliasProviderInterface
{
    protected static $classes = [
        'Oro\Bundle\TestFrameworkBundle\Entity\Item',
        'Oro\Bundle\TestFrameworkBundle\Entity\ItemValue',
        'Oro\Bundle\TestFrameworkBundle\Entity\Product'
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
