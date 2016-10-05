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
        $repository = $this->getLoginHistoryRepository();

        $cumulativeCount = $this->getLoginHistoryRepository()->countUserCumulativeFailedLogins($user);
        $dailyCount = $this->getLoginHistoryRepository()->countUserDailyFailedLogins($user);

        $maxAttempts = $this->configManager->get(self::MAX_LOGIN_ATTEMPTS);
        $maxDailyAttempts = $this->configManager->get(self::MAX_DAILY_LOGIN_ATTEMPTS);

        // subtract current values from the configured and take the minimum
        return max(0, min($maxAttempts - $cumulativeCount, $maxDailyAttempts - $dailyCount));
    }

    /**
     * @return LoginHistoryRepository
     */
    protected function getLoginHistoryRepository()
    {
        return $this->doctrine->getRepository('OroUserBundle:LoginHistory');
    }
}
