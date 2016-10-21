<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\FailedLoginInfoInterface;

class LoginAttemptsProvider
{
    const LIMIT_ENABLED = 'oro_user.failed_login_limit_enabled';
    const LIMIT = 'oro_user.failed_login_limit';

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
     * @param  FailedLoginInfoInterface $user
     * @return int
     */
    public function getRemaining(FailedLoginInfoInterface $user)
    {
        return max(0, $this->getLimit() - $user->getFailedLoginCount());
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int) $this->configManager->get(self::LIMIT);
    }

    /**
     * @return bool
     */
    public function hasLimit()
    {
        return (bool) $this->configManager->get(self::LIMIT_ENABLED);
    }

    /**
     * @param  FailedLoginInfoInterface $user
     * @return bool
     */
    public function hasReachedLimit(FailedLoginInfoInterface $user)
    {
        return $this->hasLimit() && 0 == $this->getRemaining($user);
    }
}
