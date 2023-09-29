<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Provides a text representation of enum entities.
 */
class EnumEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof AbstractEnumValue) {
            return false;
        }

        return $entity->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, AbstractEnumValue::class, true)) {
            return false;
        }

        return sprintf('%s.name', $alias);
    }
}
