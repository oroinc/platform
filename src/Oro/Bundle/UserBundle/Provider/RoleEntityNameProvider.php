<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

/**
 * @TODO this class is a workaround and should be removed after implementation of
 * entity name representation configuration.
 */
class RoleEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = AbstractRole::class;

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, static::CLASS_NAME)) {
            /** @var AbstractRole $entity */
            return $entity->getLabel();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === EntityNameProviderInterface::FULL && $className === static::CLASS_NAME) {
            return sprintf('%s.label', $alias);
        }

        return false;
    }
}
