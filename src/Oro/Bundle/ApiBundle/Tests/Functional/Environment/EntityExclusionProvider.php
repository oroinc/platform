<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Excludes some test entities to test that globally excluded entities can be used in API
 * when "exclude: false" is set in api.yml.
 */
class EntityExclusionProvider implements ExclusionProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function isIgnoredEntity($className)
    {
        return
            $className === TestDepartment::class
            || $className === TestEmployee::class;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return false;
    }
}
