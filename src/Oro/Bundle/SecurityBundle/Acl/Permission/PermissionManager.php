<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The manager for security permissions.
 */
class PermissionManager
{
    private const CACHE_PERMISSIONS = 'permissions';
    private const CACHE_GROUPS = 'groups';

    private ?array $groups = null;
    private ?array $permissions = null;
    private array $loadedPermissions = [];

    public function __construct(
        private ManagerRegistry $doctrine,
        private CacheInterface $cache
    ) {
    }

    public function processPermissions(Collection $permissions): Collection
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

        $this->buildCache(true);

        return $processedPermissions;
    }

    /**
     * @return array [permission name => permission id, ...]
     */
    public function getPermissionsMap(?string $groupName = null): array
    {
        $this->normalizeGroupName($groupName);

        return $groupName ? $this->findGroupPermissions($groupName) : $this->findPermissions();
    }

    /**
     * @return Permission[]
     */
    public function getPermissionsForEntity(string $entityClass, ?string $groupName = null): array
    {
        $this->normalizeGroupName($groupName);

        $ids = $groupName ? $this->findGroupPermissions($groupName) : null;

        return $this->getRepository()->findByEntityClassAndIds($entityClass, $ids);
    }

    /**
     * @return Permission[]
     */
    public function getPermissionsForGroup(?string $groupName): array
    {
        $this->normalizeGroupName($groupName);

        $ids = $this->findGroupPermissions($groupName);

        return $ids ? $this->getRepository()->findBy(['id' => $ids], ['id' => 'ASC']) : [];
    }

    public function getPermissionByName(string $name): ?Permission
    {
        if (!\array_key_exists($name, $this->loadedPermissions)) {
            $map = $this->getPermissionsMap();
            if (isset($map[$name])) {
                $this->loadedPermissions[$name] = $this->getEntityManager()
                    ->getReference(Permission::class, $map[$name]);
            } else {
                $this->loadedPermissions[$name] = null;
            }
        }

        return $this->loadedPermissions[$name];
    }

    private function buildCache(bool $save = false): array
    {
        /** @var Permission[] $permissions */
        $permissions = $this->getRepository()->findBy([], ['id' => 'ASC']);

        $cache = [
            self::CACHE_GROUPS => [],
            self::CACHE_PERMISSIONS => [],
        ];

        foreach ($permissions as $permission) {
            $cache[self::CACHE_PERMISSIONS][$permission->getName()] = $permission->getId();

            foreach ($permission->getGroupNames() as $group) {
                $cache[self::CACHE_GROUPS][$group][$permission->getName()] = $permission->getId();
            }
        }

        if ($save) {
            $this->cache->clear();
            foreach ($cache as $key => $value) {
                $this->cache->get($key, function () use ($value) {
                    return $value;
                });
            }
        }

        return $cache;
    }

    /**
     * @return array [permission name => permission id, ...]
     */
    private function findPermissions(): array
    {
        if (null === $this->permissions) {
            $this->permissions = $this->getCache(self::CACHE_PERMISSIONS);
        }

        return $this->permissions;
    }

    /**
     * @return array [permission name => permission id, ...]
     */
    private function findGroupPermissions(?string $name): array
    {
        if (null === $this->groups) {
            $this->groups = $this->getCache(self::CACHE_GROUPS);
        }

        return \array_key_exists($name, $this->groups) ? $this->groups[$name] : [];
    }

    private function getCache(string $key): array
    {
        return $this->cache->get($key, function () use ($key) {
            $data = $this->buildCache();
            return !empty($data[$key]) ? $data[$key] : [];
        });
    }

    private function normalizeGroupName(?string &$groupName): void
    {
        if ($groupName !== null && empty($groupName)) {
            $groupName = PermissionConfiguration::DEFAULT_GROUP_NAME;
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Permission::class);
    }

    private function getRepository(): PermissionRepository
    {
        return $this->doctrine->getRepository(Permission::class);
    }
}
