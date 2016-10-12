<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginAttemptsProvider
{
    const MAX_LOGIN_ATTEMPTS = 'oro_user.login_attempts';
    const MAX_DAILY_LOGIN_ATTEMPTS = 'oro_user.daily_login_attempts';

    /** @var Registry */
    protected $doctrine;

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
        Registry $registry,
        ConfigManager $configManager,
        BaseUserManager $userManager
    ) {
        $this->doctrine = $registry;
        $this->configManager = $configManager;
        $this->userManager = $userManager;
    }

    public function getByUsername($username)
    {
        if ($user = $this->userManager->findUserByUsername($username)) {
            return $this->getByUser($user);
        }

        return null;
    }

    public function getByUser(UserInterface $user)
    {
        $remainingCumulative = $this->getRemainingCumulativeLoginAttempts($user);
        $remainingDaily = $this->getRemainingDailyLoginAttempts($user);

        return max(0, min($remainingCumulative, $remainingDaily));
    }

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

    public function getRemainingCumulativeLoginAttempts(UserInterface $user)
    {
        $cumulativeCount = $this->getLoginHistoryRepository()->countUserCumulativeFailedLogins($user);

        return $this->getMaxCumulativeLoginAttempts() - $cumulativeCount;
    }

    public function getRemainingDailyLoginAttempts(UserInterface $user)
    {
        $dailyCount = $this->getLoginHistoryRepository()->countUserDailyFailedLogins($user);

        return $this->getMaxDailyLoginAttempts() - $dailyCount;
    }

    public function getMaxCumulativeLoginAttempts()
    {
        return $this->configManager->get(self::MAX_LOGIN_ATTEMPTS);
    }

    public function getMaxDailyLoginAttempts()
    {
        return $this->configManager->get(self::MAX_DAILY_LOGIN_ATTEMPTS);
    }

    /**
     * @return LoginHistoryRepository
     */
    protected function getLoginHistoryRepository()
    {
        return $this->doctrine->getRepository('OroUserBundle:LoginHistory');
    }
}
