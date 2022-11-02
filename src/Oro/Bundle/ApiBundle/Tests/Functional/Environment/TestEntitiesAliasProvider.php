<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class TestEntitiesAliasProvider implements EntityAliasProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (!str_starts_with($entityClass, 'Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity')) {
            return null;
        }

        $name = str_replace('Test', 'TestApi', substr($entityClass, strrpos($entityClass, '\\') + 1));

        return new EntityAlias(
            strtolower($name),
            strtolower((new InflectorFactory())->build()->pluralize($name))
        );
    }
}
