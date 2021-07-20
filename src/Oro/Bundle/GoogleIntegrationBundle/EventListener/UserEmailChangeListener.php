<?php

namespace Oro\Bundle\GoogleIntegrationBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Clears up Google single sign-on authentication identifier when user's email is changed.
 */
class UserEmailChangeListener
{
    public function preUpdate(User $user, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('email')) {
            $user->setGoogleId(null);
        }
    }
}
