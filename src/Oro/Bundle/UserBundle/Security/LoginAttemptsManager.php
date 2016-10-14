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
        // reset daily counter if last failed login was before midnight
        $midnight = new \DateTime('midnight', new \DateTimeZone('UTC'));
        if ($user->getLastFailedLogin() < $midnight) {
            $user->setDailyFailedLoginCount(0);
        }

        $user->setLastFailedLogin(new \DateTime('now', new \DateTimeZone('UTC')));
        $user->setFailedLoginCount($user->getFailedLoginCount() + 1);
        $user->setDailyFailedLoginCount($user->getDailyFailedLoginCount() + 1);

        if (!$this->attemptsProvider->hasRemainingAttempts($user)) {
            $this->deactivateUser($user);
        }

        $this->userManager->updateUser($user);
    }

    /**
     * @param AbstractUser $user
     */
    protected function resetFailedLoginCounters(AbstractUser $user)
    {
        $user->setFailedLoginCount(0);
        $user->setDailyFailedLoginCount(0);
        $this->userManager->updateUser($user);
    }

    /**
     * Disable/Deactivate an user and sends notification email to them and to administrators
     *
     * @param AbstractUser $user
     */
    protected function deactivateUser(AbstractUser $user)
    {
        $user->setEnabled(false);

        if ($this->attemptsProvider->getRemainingCumulativeLoginAttempts($user) <= 0) {
            $this->mailProcessor->sendAutoDeactivateEmail(
                $user,
                $this->attemptsProvider->getMaxCumulativeLoginAttempts($user)
            );

            return;
        }

        if ($this->attemptsProvider->getRemainingDailyLoginAttempts($user) <= 0) {
            $this->mailProcessor->sendAutoDeactivateDailyEmail(
                $user,
                $this->attemptsProvider->getMaxDailyLoginAttempts($user)
            );

            return;
        }
    }
}
