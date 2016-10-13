<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository;

class LoginAttemptsProvider
{
    const MAX_LOGIN_ATTEMPTS = 'oro_user.login_attempts';
    const MAX_DAILY_LOGIN_ATTEMPTS = 'oro_user.daily_login_attempts';

    /** @var  ConfigManager */
    protected $configManager;

    /** @var BaseUserManager */
    protected $userManager;

    /**
     * @param Registry        $registry
     * @param ConfigManager   $configManager
     * @param BaseUserManager $userManager
     */
    public function __construct(
        ConfigManager $configManager,
        BaseUserManager $userManager
    ) {
        $this->configManager = $configManager;
        $this->userManager = $userManager;
    }

    /**
     * @param  string $username
     * @return int|null Return null if user with supplied username does not exists
     */
    public function getByUsername($username)
    {
        if ($user = $this->userManager->findUserByUsername($username)) {
            return $this->getByUser($user);
        }

        return null;
    }

    /**
     * Get remaining login attempts by used
     *
     * @param  UserInterface $user
     * @return int
     */
    public function getByUser(UserInterface $user)
    {
        $remainingCumulative = $this->getRemainingCumulativeLoginAttempts($user);
        $remainingDaily = $this->getRemainingDailyLoginAttempts($user);

        return max(0, min($remainingCumulative, $remainingDaily));
    }

    /**
     * Get exceed login attempts limit - daily or cumulative.
     * Return zero when limits are not exceed.
     *
     * @param  UserInterface $user
     * @return int
     */
    public function getExceedLimit(UserInterface $user)
    {
        if ($this->getRemainingCumulativeLoginAttempts($user) <= 0) {
            return $this->getMaxCumulativeLoginAttempts();
        }

        if ($this->getRemainingDailyLoginAttempts($user) <= 0) {
            return $this->getMaxDailyLoginAttempts();
        }

        return 0;
    }

    public function hasRemainingAttempts(UserInterface $user)
    {
        return 0 !== $this->getByUser($user);
    }

    /**
     * @param  UserInterface $user
     * @return int
     */
    public function getRemainingCumulativeLoginAttempts(UserInterface $user)
    {
        return $this->getMaxCumulativeLoginAttempts() - $user->getFailedLoginCount();
    }

    /**
     * @param  UserInterface $user
     * @return int
     */
    public function getRemainingDailyLoginAttempts(UserInterface $user)
    {
        return $this->getMaxDailyLoginAttempts() - $user->getDailyFailedLoginCount();
    }

    /**
     * @return int
     */
    public function getMaxCumulativeLoginAttempts()
    {
        return $this->configManager->get(self::MAX_LOGIN_ATTEMPTS);
    }

    /**
     * @return int
     */
    public function getMaxDailyLoginAttempts()
    {
        return $this->configManager->get(self::MAX_DAILY_LOGIN_ATTEMPTS);
    }
}
