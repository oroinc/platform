<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Cleans user IMAP configuration when primary email of user changed
 */
class UserEmailChangeListener
{
    /**
     * @param User $user
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(User $user, PreUpdateEventArgs $args)
    {
        if (!$args->hasChangedField('email')) {
            return;
        }

        $userEmailOrigin = $user->getImapConfiguration();
        if ($userEmailOrigin) {
            $user->setImapConfiguration(null);

            $em = $args->getEntityManager();
            $em->getUnitOfWork()
                ->computeChangeSet(
                    $em->getClassMetadata(UserEmailOrigin::class),
                    $userEmailOrigin
                );
        }
    }
}
