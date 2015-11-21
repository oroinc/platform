<?php

namespace Oro\Bundle\SecurityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeProvider
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
     * @param array $shareScopes
     *
     * @return array
     */
    public function getClassNamesBySharingScopes(array $shareScopes)
    {
        $result = [];
        foreach ($shareScopes as $shareScope) {
            if ($shareScope === Share::SHARE_SCOPE_USER) {
                array_push($result, 'Oro\Bundle\UserBundle\Entity\User');
            } elseif ($shareScope === Share::SHARE_SCOPE_BUSINESS_UNIT) {
                array_unshift($result, 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit');
            }
        }

        return $result;
    }

    /**
     * Returns class names according to share scopes using entity config. The goal is to determine on which
     * database tables search should be performed.
     *
     * @param string $entityClass
     *
     * @return array
     */
    public function getClassNamesBySharingScopeConfig($entityClass)
    {
        $classNames = [];
        $shareScopes = $this->configManager->getProvider('security')->getConfig($entityClass)->get('share_scopes');
        if (!$shareScopes) {
            return $classNames;
        }

        return $this->getClassNamesBySharingScopes($shareScopes);
    }
}
