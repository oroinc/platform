<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\FailedLoginInfoInterface;

class LoginAttemptsProvider
{
    const MAX_LOGIN_ATTEMPTS = 'oro_user.login_attempts';
    const MAX_DAILY_LOGIN_ATTEMPTS = 'oro_user.daily_login_attempts';

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param Registry        $registry
     * @param ConfigManager   $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Get remaining login attempts by used
     *
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getByUser(FailedLoginInfoInterface $user)
    {
        $remainingCumulative = $this->getRemainingCumulativeLoginAttempts($user);
        $remainingDaily = $this->getRemainingDailyLoginAttempts($user);

        return max(0, min($remainingCumulative, $remainingDaily));
    }

    /**
     * Get exceed login attempts limit - daily or cumulative.
     * Return zero when limits are not exceed.
     *
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getExceedLimit(FailedLoginInfoInterface $user)
    {
        if ($this->getRemainingCumulativeLoginAttempts($user) <= 0) {
            return $this->getMaxCumulativeLoginAttempts();
        }

        if ($this->getRemainingDailyLoginAttempts($user) <= 0) {
            return $this->getMaxDailyLoginAttempts();
        }

        return 0;
    }

    public function hasRemainingAttempts(FailedLoginInfoInterface $user)
    {
        return 0 !== $this->getByUser($user);
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemainingCumulativeLoginAttempts(FailedLoginInfoInterface $user)
    {
        return $this->getMaxCumulativeLoginAttempts() - $user->getFailedLoginCount();
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemainingDailyLoginAttempts(FailedLoginInfoInterface $user)
    {
        return $this->getMaxDailyLoginAttempts() - $user->getDailyFailedLoginCount();
    }

    /**
     * @return int
     */
    public function getMaxCumulativeLoginAttempts()
    {
        return (int) $this->configManager->get(self::MAX_LOGIN_ATTEMPTS);
    }

    /**
     * @return int
     */
    public function getMaxDailyLoginAttempts()
    {
        return (int) $this->configManager->get(self::MAX_DAILY_LOGIN_ATTEMPTS);
    }
}
