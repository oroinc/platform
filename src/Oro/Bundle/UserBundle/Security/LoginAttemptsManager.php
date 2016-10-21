<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsManager
{
    /** @var LoginAttemptsProvider */
    protected $attemptsProvider;

    /** @var BaseUserManager */
    protected $userManager;

    /** @var Processor */
    protected $mailProcessor;

    /**
     * @param LoginAttemptsProvider $attemptsProvider
     * @param BaseUserManager $userManager
     * @param Processor $mailProcessor
     */
    public function __construct(
        LoginAttemptsProvider $attemptsProvider,
        BaseUserManager $userManager,
        Processor $mailProcessor
    ) {
        $this->attemptsProvider = $attemptsProvider;
        $this->userManager = $userManager;
        $this->mailProcessor = $mailProcessor;
    }

    /**
     * @param AbstractUser $user
     */
    public function trackLoginSuccess(AbstractUser $user)
    {
        $this->resetFailedLoginCounters($user);
    }

    /**
     * Update login counter and deactivate the user when limits are exceeded
     *
     * @param AbstractUser $user
     */
    public function trackLoginFailure(AbstractUser $user)
    {
        if (!$this->attemptsProvider->hasLimit()) {
            return;
        }

        $user->setFailedLoginCount($user->getFailedLoginCount() + 1);

        if ($this->attemptsProvider->hasReachedLimit($user)) {
            $user->setEnabled(false);
            $this->mailProcessor->sendAutoDeactivateEmail($user, $this->attemptsProvider->getLimit());
        }

        $this->userManager->updateUser($user);
    }

    /**
     * @param AbstractUser $user
     */
    protected function resetFailedLoginCounters(AbstractUser $user)
    {
        if ($this->attemptsProvider->hasLimit()) {
            $user->setFailedLoginCount(0);
            $this->userManager->updateUser($user);
        }
    }
}
