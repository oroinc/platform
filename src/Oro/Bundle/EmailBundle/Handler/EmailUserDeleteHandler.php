<?php

namespace Oro\Bundle\EmailBundle\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;

/**
 * The delete handler for EmailUser entity.
 */
class EmailUserDeleteHandler extends AbstractEntityDeleteHandler
{
    #[\Override]
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var EmailUser $entity */

        parent::deleteWithoutFlush($entity, $options);

        $entity->getEmail()?->removeEmailUser($entity);
    }
}
