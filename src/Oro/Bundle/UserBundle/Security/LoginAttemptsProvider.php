<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\FailedLoginInfoInterface;

class LoginAttemptsProvider
{
    const LIMIT_ENABLED = 'oro_user.failed_login_limit_enabled';
    const LIMIT = 'oro_user.failed_login_limit';
    const DAILY_LIMIT_ENABLED = 'oro_user.failed_daily_login_limit_enabled';
    const DAILY_LIMIT = 'oro_user.failed_daily_login_limit';

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager   $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Get remaining login attempts by user
     *
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemaining(FailedLoginInfoInterface $user)
    {
        $limits = [];

        if ($this->hasCumulativeLimit()) {
            $limits[] = $this->getRemainingCumulativeLoginAttempts($user);
        }

        if ($this->hasDailyLimit()) {
            $limits[] = $this->getRemainingDailyLoginAttempts($user);
        }

        return empty($limits) ? 0 : min($limits);
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemainingCumulativeLoginAttempts(FailedLoginInfoInterface $user)
    {
        return max(0, $this->getMaxCumulativeLoginAttempts() - $user->getFailedLoginCount());
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemainingDailyLoginAttempts(FailedLoginInfoInterface $user)
    {
        return max(0, $this->getMaxDailyLoginAttempts() - $user->getDailyFailedLoginCount());
    }

    /**
     * @return int
     */
    public function getMaxCumulativeLoginAttempts()
    {
        return (int) $this->configManager->get(self::LIMIT);
    }

    /**
     * @return int
     */
    public function getMaxDailyLoginAttempts()
    {
        return (int) $this->configManager->get(self::DAILY_LIMIT);
    }

    /**
     * @return bool
     */
    public function hasCumulativeLimit()
    {
        return (bool) $this->configManager->get(self::LIMIT_ENABLED);
    }

    /**
     * @return bool
     */
    public function hasDailyLimit()
    {
        return (bool) $this->configManager->get(self::DAILY_LIMIT_ENABLED);
    }

    /**
     * @return bool
     */
    public function hasLimits()
    {
        return $this->hasCumulativeLimit() || $this->hasDailyLimit();
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return bool
     */
    public function hasReachedCumulativeLimit(FailedLoginInfoInterface $user)
    {
        return $this->hasCumulativeLimit() && 0 == $this->getRemainingCumulativeLoginAttempts($user);
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return bool
     */
    public function hasReachedDailyLimit(FailedLoginInfoInterface $user)
    {
        return $this->hasDailyLimit() && 0 == $this->getRemainingDailyLoginAttempts($user);
    }
}
