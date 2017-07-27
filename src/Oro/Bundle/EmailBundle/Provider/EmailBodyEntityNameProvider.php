<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class EmailBodyEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof EmailBody) {
            return false;
        }

        return $entity->getBodyIsText() ? $entity->getTextBody() : $entity->getBodyContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($className !== EmailBody::class) {
            return false;
        }

        return sprintf('CASE WHEN %1$s.bodyIsText = true THEN %1$s.textBody ELSE %1$s.bodyContent END', $alias);
    }
}
