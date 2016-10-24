<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;

class ResetHandler extends AbstractUserHandler
{
    /**
     * {@inheritDoc}
     */
    protected function onSuccess(User $user)
    {
        $user
            ->setPlainPassword($this->form->getData()->getPlainPassword())
            ->setLoginDisabled(false)
            ->setConfirmationToken(null)
            ->setPasswordRequestedAt(null)
            ->setEnabled(true);

        $this->manager->updateUser($user);
    }
}
