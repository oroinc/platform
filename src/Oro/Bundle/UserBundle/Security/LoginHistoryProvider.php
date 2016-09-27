<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository;

class LoginHistoryProvider
{
    const MAX_LOGIN_ATTEMPTS = 'oro_user.login_attempts';
    const MAX_DAILY_LOGIN_ATTEMPTS = 'oro_user.daily_login_attempts';

    /** @var Registry */
    protected $doctrine;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  LoginHistoryManager */
    protected $loginHistoryManager;

    public function __construct(
        Registry $registry,
        ConfigManager $configManager,
        LoginHistoryManager $loginHistoryManager
    ) {
        $this->doctrine = $registry;
        $this->configyManager = $configManager;
        $this->loginHistoryManager = $loginHistoryManager;
    }

    public function getRemainingLoginAttempts(UserInterface $user, $providerClass = null)
    {
        // fetch login history for $user and $providerClass (which may be null) order by failedTotal DESC LIMIT 1
        $repository = $this->getLoginHistoryRepository();
        $loginHistory = null;

        if ($providerClass) {
            $loginHistory = $repository->getByUserAndProviderClass($user, $providerClass);
        } else {
            $loginHistory = $repository->getByUserAndMaxFailedAttempts($user);
        }

        if (!$loginHistory) {
            $loginHistory = $this->loginHistoryManager->createLoginHistory($user, $providerClass);
        }

        $consumedAttempts = $loginHistory ? $loginHistory->getFailedAttempts() : 0;

        $maxAttempts = $this->configManager->get(self::MAX_LOGIN_ATTEMPTS);
        $maxDailyAttempts = $this->configManager->get(self::MAX_DAILY_LOGIN_ATTEMPTS);

        // subtract current value from the configured one
        return min(($maxAttempts-$consumedAttempts), ($maxDailyAttempts-$consumedAttempts));
    }

    public function getRemainingDailyLoginAttempts(UserInterface $user, $providerClass = null)
    {
        // fetch login history for $user and $providerClass (which may be null) order by failedDaily DESC LIMIT 1
    }

    /**
     * @return LoginHistoryRepository
     */
    protected function getLoginHistoryRepository()
    {
        return $this->doctrine->getRepository('OroUserBundle:LoginHistory');
    }
}
