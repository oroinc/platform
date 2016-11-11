<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class EmailUserEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!in_array($format, [self::SHORT, self::FULL])) {
            return false;
        }

        return is_a($entity, EmailUser::class, true) ? $entity->getEmail()->getSubject() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, EmailUser::class, true)) {
            return false;
        }

        return sprintf(
            'CAST((' .
            'SELECT %1$s_base.subject FROM %2$s %1$s_base WHERE %1$s_base = %1$s' .
            ') AS string)',
            $alias,
            $className
        );
    }
}
