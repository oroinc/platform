<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class EnumEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof AbstractEnumValue) {
            return false;
        }

        return $entity->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, AbstractEnumValue::class, true)) {
            return false;
        }

        return sprintf('%s.name', $alias);
    }
}
