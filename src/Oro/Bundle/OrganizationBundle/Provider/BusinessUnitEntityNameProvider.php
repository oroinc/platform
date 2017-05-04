<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class BusinessUnitEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, self::CLASS_NAME)) {
            return $entity->getName();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === EntityNameProviderInterface::FULL && $className === self::CLASS_NAME) {
            return sprintf('%s.name', $alias);
        }

        return false;
    }
}
