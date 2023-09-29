<?php

namespace Oro\Bundle\EmailBundle\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;

/**
 * The delete handler extension for EmailUser entity.
 */
class EmailUserDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /**
     * {@inheritDoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var EmailUser $entity */

        $email = $entity->getEmail();
        if (null === $email) {
            return;
        }

        $hasOtherEmailUsers = false;
        $emailUsers = $email->getEmailUsers();
        foreach ($emailUsers as $emailUser) {
            if ($emailUser->getId() !== $entity->getId()) {
                $hasOtherEmailUsers = true;
                break;
            }
        }
        if (!$hasOtherEmailUsers) {
            throw $this->createAccessDeniedException('an email should have at least one email user');
        }
    }
}
