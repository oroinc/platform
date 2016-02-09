<?php
namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;

class PermissionManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PermissionConfigurationProvider */
    protected $configurationProvider;

    /** @var PermissionConfigurationBuilder */
    protected $configurationBuilder;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PermissionConfigurationProvider $configurationProvider,
        PermissionConfigurationBuilder $configurationBuilder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationProvider = $configurationProvider;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * @param array $acceptedPermissions
     * @return Permission[]
     */
    public function getPermissionsFromConfig(array $acceptedPermissions = null)
    {
        $permissionConfiguration = $this->configurationProvider->getPermissionConfiguration(
            $acceptedPermissions
        );

        return $this->configurationBuilder
            ->buildPermissions($permissionConfiguration[PermissionConfigurationProvider::ROOT_NODE_NAME]);
    }

    /**
     * @param Permission $permission
     * @return Permission
     */
    public function preparePermissionForDb(Permission $permission)
    {
        /** @var Permission $existingPermission */
        $existingPermission = $this->getRepository()->findOneBy(['name' => $permission->getName()]);

        // permission in DB should be overridden if permission with such name already exists
        if ($existingPermission) {
            $existingPermission->import($permission);
        }

        return $existingPermission ?: $permission;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroSecurityBundle:Permission');
    }
}
