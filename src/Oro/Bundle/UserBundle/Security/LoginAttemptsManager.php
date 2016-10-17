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
        if ($this->attemptsProvider->hasDailyLimit()) {
            // reset daily counter if last failed login was before midnight
            $midnight = new \DateTime('midnight', new \DateTimeZone('UTC'));
            if ($user->getLastFailedLogin() && $user->getLastFailedLogin() < $midnight) {
                $user->setDailyFailedLoginCount(0);
            }

            $user->setDailyFailedLoginCount($user->getDailyFailedLoginCount() + 1);
        }

        if ($this->attemptsProvider->hasCumulativeLimit()) {
            $user->setFailedLoginCount($user->getFailedLoginCount() + 1);
        }

        $this->processDeactivation($user);

        $user->setLastFailedLogin(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->userManager->updateUser($user);
    }

    /**
     * @param AbstractUser $user
     */
    protected function resetFailedLoginCounters(AbstractUser $user)
    {
        if ($this->attemptsProvider->hasCumulativeLimit()) {
            $user->setFailedLoginCount(0);
        }

        if ($this->attemptsProvider->hasDailyLimit()) {
            $user->setDailyFailedLoginCount(0);
        }

        // either limit is enabled
        if ($this->attemptsProvider->hasLimits()) {
            $this->userManager->updateUser($user);
        }
    }

    /**
     * Disable/Deactivate a user and sends notification email to them and to administrators
     *
     * @param AbstractUser $user
     */
    protected function processDeactivation(AbstractUser $user)
    {
        if ($this->attemptsProvider->hasReachedCumulativeLimit($user)) {
            $user->setEnabled(false);
            $this->mailProcessor->sendAutoDeactivateEmail(
                $user,
                $this->attemptsProvider->getMaxCumulativeLoginAttempts()
            );

            return;
        }

        if ($this->attemptsProvider->hasReachedDailyLimit($user)) {
            $user->setEnabled(false);
            $this->mailProcessor->sendAutoDeactivateDailyEmail(
                $user,
                $this->attemptsProvider->getMaxDailyLoginAttempts()
            );

            return;
        }
    }
}
