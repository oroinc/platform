<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Handle Reset password forms
 */
class ResetHandler extends AbstractUserHandler
{
    /**
     * {@inheritDoc}
     */
    protected function onSuccess(User $user)
    {
        $user
            ->setPlainPassword($this->form->getData()->getPlainPassword())
            ->setConfirmationToken(null)
            ->setPasswordRequestedAt(null);

        $this->manager->setAuthStatus($user, UserManager::STATUS_ACTIVE);
        $this->manager->updateUser($user);
    }
}
