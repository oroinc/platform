<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle Reset password forms.
 */
class ResetHandler extends AbstractUserHandler
{
    private LoggerInterface $logger;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        UserManager $manager,
        LoggerInterface $logger
    ) {
        parent::__construct($form, $requestStack, $manager);

        $this->logger = $logger;
    }

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

        $this->logger->notice(
            'Password was successfully reset for user.',
            ['user_id' => $user->getId()]
        );
    }

    protected function onFail(User $user): void
    {
        $this->logger->notice(
            'Password reset for user was failed.',
            ['user_id' => $user->getId()]
        );
    }
}
