<?php

namespace Oro\Bundle\ConfigBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;

class UserConfigManager
{
    /** @var UserScopeManager */
    protected $userScopeManager;

    /**
     * @param UserScopeManager $userScopeManager
     */
    public function __construct(UserScopeManager $userScopeManager = null) {
        $this->userScopeManager = $userScopeManager;
    }

    /**
     * @param string $signature
     * @return bool
     */
    public function saveUserConfigSignature($signature)
    {
        if ($signature === '') {
            $signature = null;
        }
        $newSettings = [implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_email', 'signature']) => $signature];
        return $this->userScopeManager->save($newSettings);
    }

    /**
     * @return null|string
     */
    public function getUserConfigSignature()
    {
        return $this->userScopeManager->getSettingValue('oro_email.signature');
    }
}
