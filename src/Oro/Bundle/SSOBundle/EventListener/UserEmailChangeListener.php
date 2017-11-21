<?php

namespace Oro\Bundle\SSOBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\UserBundle\Entity\User;

class UserEmailChangeListener
{
    /**
     * @param User               $user
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(User $user, PreUpdateEventArgs $args)
    {
        if (!$args->hasChangedField('email')) {
            return;
        }

        $user->setGoogleId(null);
    }
}
