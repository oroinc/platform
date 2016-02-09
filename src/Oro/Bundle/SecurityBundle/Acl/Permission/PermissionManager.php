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
        $this->cache->saveMultiple($cache);

        return $cache;
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
     * @return PermissionRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroSecurityBundle:Permission');
    }
}
