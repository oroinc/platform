<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Provides entity names for email body entities.
 *
 * Supplies human-readable names for email body entities in the system,
 * used for display and identification purposes in the UI and API.
 */
class EmailBodyEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof EmailBody) {
            return false;
        }

        return $entity->getBodyIsText() ? $entity->getTextBody() : $entity->getBodyContent();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($className !== EmailBody::class) {
            return false;
        }

        return sprintf('CASE WHEN %1$s.bodyIsText = true THEN %1$s.textBody ELSE %1$s.bodyContent END', $alias);
    }
}
