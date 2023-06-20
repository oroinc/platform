<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Provides a text representation of EmailUser entity.
 */
class EmailUserEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof EmailUser) {
            return false;
        }

        return $entity->getEmail()->getSubject();
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, EmailUser::class, true)) {
            return false;
        }

        return sprintf(
            '(SELECT %1$s_e.subject FROM %2$s %1$s_e WHERE %1$s_e = %1$s.email)',
            $alias,
            Email::class
        );
    }
}
