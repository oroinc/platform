<?php

namespace Oro\Bundle\EntityPaginationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EntityPaginationManager
{
    const VIEW_SCOPE = 'view';
    const EDIT_SCOPE = 'edit';

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
    public function isEnabled()
    {
        return (bool)$this->configManager->get('oro_entity_pagination.enabled');
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int)$this->configManager->get('oro_entity_pagination.limit');
    }

    /**
     * @param string $scope
     * @return string
     * @throws \LogicException
     */
    public static function getPermission($scope)
    {
        switch ($scope) {
            case self::VIEW_SCOPE:
                $permission = 'VIEW';
                break;
            case self::EDIT_SCOPE:
                $permission = 'EDIT';
                break;
            default:
                throw new \LogicException(sprintf('Scope "%s" is not available.', $scope));
        }

        return $permission;
    }
}
