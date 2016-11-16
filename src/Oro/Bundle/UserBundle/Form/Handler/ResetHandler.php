<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Handle Reset password forms
 */
class ResetHandler extends AbstractUserHandler
{
    const STATUS_AVAILABLE = 'available';

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(User $user)
    {
        $user
            ->setPlainPassword($this->form->getData()->getPlainPassword())
            ->setConfirmationToken(null)
            ->setPasswordRequestedAt(null);

        $this->manager->setAuthStatus($user, self::STATUS_AVAILABLE);
        $this->manager->updateUser($user);
    }
}
