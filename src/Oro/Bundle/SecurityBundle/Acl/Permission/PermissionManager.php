<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
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
     * @param PermissionConfigurationProvider $configurationProvider
     * @param PermissionConfigurationBuilder $configurationBuilder
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
     * @return Permission[]|Collection
     */
    public function getPermissionsFromConfig(array $acceptedPermissions = null)
    {
        $permissionConfiguration = $this->configurationProvider->getPermissionConfiguration(
            $acceptedPermissions
        );

        return $this->configurationBuilder
            ->buildPermissions($permissionConfiguration);
    }

    /**
     * @param Permission[]|Collection $permissions
     * @return Permission[]|Collection
     */
    public function processPermissions(Collection $permissions)
    {
        $entityRepository = $this->getRepository();
        $entityManager = $this->getEntityManager();
        foreach ($permissions as &$permission) {
            /** @var Permission $existingPermission */
            $existingPermission = $entityRepository->findOneBy(['name' => $permission->getName()]);

            // permission in DB should be overridden if permission with such name already exists
            if ($existingPermission) {
                $existingPermission->import($permission);
                $permission = $existingPermission;
            }
            $entityManager->persist($permission);
        }
        unset($permission);

        $entityManager->flush();

        return $permissions;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass('OroSecurityBundle:Permission');
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroSecurityBundle:Permission');
    }
}
