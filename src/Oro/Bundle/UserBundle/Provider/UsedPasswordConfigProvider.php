<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class UsedPasswordConfigProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    public function isUsedPasswordCheckEnabled()
    {
        return (bool) $this->configManager->get('oro_user.used_password_check_enabled');
    }

    /**
     * @return int
     */
    public function getUsedPasswordsCheckNumber()
    {
        return (int) $this->configManager->get('oro_user.used_password_check_number');
    }
}
