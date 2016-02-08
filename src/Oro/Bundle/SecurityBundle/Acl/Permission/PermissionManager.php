<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

class PermissionManager
{
    const CACHE_PERMISSIONS = 'permissions';
    const CACHE_GROUPS = 'groups';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $groups;

    /** @var array */
    protected $permissions;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CacheProvider $cache
     */
    public function __construct(DoctrineHelper $doctrineHelper, CacheProvider $cache)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
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

        $ids = $groupName ? $this->findPermissionsIdsByGroupName($groupName) : null;

        return $repository->findByEntityClassAndIds($this->doctrineHelper->getEntityClass($entity), $ids);
    }

    /**
     * @param Permission[] $permissions
     */
    public function buildCache()
    {
        $permissions = $this->getRepository()->findAll();

        $data = [];
        foreach ($permissions as $permission) {
            $data[static::CACHE_PERMISSIONS][$permission->getName()] = $permission->getId();

            foreach ($permission->getGroupNames() as $group) {
                $data[static::CACHE_GROUPS][$group][] = $permission->getName();
            }
        }

        $this->cache->flushAll();
        $this->cache->saveMultiple($data);
    }

    /**
     * @param string $name
     * @return array
     */
    protected function findPermissionsIdsByGroupName($name = '')
    {
        $permissions = $this->findGroups($name);

        $ids = array_intersect_key($this->findPermissions(), array_combine($permissions, $permissions));

        return $ids;
    }

    /**
     * @param string $name
     * @return array|int
     */
    protected function findPermissions($name = '')
    {
        if (null === $this->permissions) {
            $this->permissions = $this->cache->fetch(static::CACHE_PERMISSIONS);
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
            $this->groups = $this->cache->fetch(static::CACHE_GROUPS);
        }

        if ($name) {
            return isset($this->groups[$name]) ? $this->groups[$name] : [];
        }

        return $this->groups;
    }

    /**
     * @return PermissionRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroSecurityBundle:Permission');
    }
}
