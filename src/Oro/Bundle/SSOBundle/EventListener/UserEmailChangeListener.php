<?php

namespace Oro\Bundle\SSOBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\UserBundle\Entity\User;

class UserEmailChangeListener
{
    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (!$args->getEntity() instanceof User || !$args->hasChangedField('email')) {
            return;
        }

        $args->getEntity()->setGoogleId(null);
    }
}
