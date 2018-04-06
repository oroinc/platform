<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class TestEntitiesAliasProvider implements EntityAliasProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (0 !== strpos($entityClass, 'Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity')) {
            return null;
        }

        $name = str_replace('Test', 'TestApi', substr($entityClass, strrpos($entityClass, '\\') + 1));

        return new EntityAlias(
            strtolower($name),
            strtolower(Inflector::pluralize($name))
        );
    }
}
