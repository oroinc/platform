<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\UserBundle\Entity\User;

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
            $args->getEntityManager()->flush($userEmailOrigin);
        }
    }
}
