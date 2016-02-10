<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

class PermissionManager
{
    const CACHE_PERMISSIONS = 'permissions';
    const CACHE_GROUPS = 'groups';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PermissionConfigurationProvider */
    protected $configurationProvider;

    /** @var PermissionConfigurationBuilder */
    protected $configurationBuilder;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $groups;

    /** @var array */
    protected $permissions;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PermissionConfigurationProvider $configurationProvider
     * @param PermissionConfigurationBuilder $configurationBuilder
     * @param CacheProvider $cache
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PermissionConfigurationProvider $configurationProvider,
        PermissionConfigurationBuilder $configurationBuilder,
        CacheProvider $cache
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationProvider = $configurationProvider;
        $this->configurationBuilder = $configurationBuilder;
        $this->cache = $cache;
    }

    /**
     * @param array|null $acceptedPermissions
     * @return Permission[]|Collection
     */
    public function getPermissionsFromConfig(array $acceptedPermissions = null)
    {
        $permissionConfiguration = $this->configurationProvider->getPermissionConfiguration($acceptedPermissions);

        return $this->configurationBuilder->buildPermissions($permissionConfiguration);
    }

    /**
     * @param Permission[]|Collection $permissions
     * @return Permission[]|Collection
     */
    public function processPermissions(Collection $permissions)
    {
        $entityRepository = $this->getRepository();
        $entityManager = $this->getEntityManager();
        $processedPermissions = new ArrayCollection();

        foreach ($permissions as $permission) {
            /** @var Permission $existingPermission */
            $existingPermission = $entityRepository->findOneBy(['name' => $permission->getName()]);

            // permission in DB should be overridden if permission with such name already exists
            if ($existingPermission) {
                $existingPermission->import($permission);
                $permission = $existingPermission;
            }

            $entityManager->persist($permission);
            $processedPermissions->add($permission);
        }

        $entityManager->flush();

        return $processedPermissions;
    }

    /**
     * @param string $groupName
     * @return array
     */
    public function getPermissionsMap($groupName = '')
    {
        return $groupName ? $this->findGroups($groupName) : $this->findPermissions();
    }

    /**
     * @param mixed $entity
     * @param string $groupName
     * @return Permission[]
     */
    public function getPermissionsForEntity($entity, $groupName = '')
    {
        $repository = $this->getRepository();

        $ids = $groupName ? $this->findGroups($groupName) : null;

        return $repository->findByEntityClassAndIds($this->doctrineHelper->getEntityClass($entity), $ids);
    }

    /**
     * @return array
     */
    public function buildCache()
    {
        $permissions = $this->getRepository()->findAll();

        $cache = [
            static::CACHE_GROUPS => [],
            static::CACHE_PERMISSIONS => [],
        ];

        foreach ($permissions as $permission) {
            $cache[static::CACHE_PERMISSIONS][$permission->getName()] = $permission->getId();

            foreach ($permission->getGroupNames() as $group) {
                $cache[static::CACHE_GROUPS][$group][$permission->getName()] = $permission->getId();
            }
        }

        $this->cache->flushAll();
        foreach($cache as $key => $value) {
            $this->cache->save($key, $value);
        }

        return $cache;
    }

    /**
     * @param string $name
     * @return array|int
     */
    protected function findPermissions($name = '')
    {
        if (null === $this->permissions) {
            $this->permissions = $this->getCache(static::CACHE_PERMISSIONS);
        }

        if ($name) {
            return isset($this->permissions[$name]) ? $this->permissions[$name] : 0;
        }

        return $this->permissions;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function findGroups($name = '')
    {
        if (null === $this->groups) {
            $this->groups = $this->getCache(static::CACHE_GROUPS);
        }

        if ($name) {
            return isset($this->groups[$name]) ? $this->groups[$name] : [];
        }

        return $this->groups;
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getCache($key)
    {
        if (false === ($cache = $this->cache->fetch($key))) {
            $data = $this->buildCache();

            return isset($data[$key]) ? $data[$key] : [];
        }

        return $cache;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass('OroSecurityBundle:Permission');
    }

    /**
     * @return PermissionRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroSecurityBundle:Permission');
    }
}
