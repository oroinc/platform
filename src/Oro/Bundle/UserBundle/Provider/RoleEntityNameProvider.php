<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Represents Lead entities by 'name' field avoiding usage of FullNameInterface.
 *
 * @TODO this class is a workaround and should be removed after implementation of
 * entity name representation configuration.
 */
class RoleEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = 'Oro\Bundle\UserBundle\Entity\Role';

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, self::CLASS_NAME)) {
            return $entity->getLabel();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === EntityNameProviderInterface::FULL && $className === self::CLASS_NAME) {
            return sprintf('%s.label', $alias);
        }

        return false;
    }
}
