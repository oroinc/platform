<?php

namespace Oro\Bundle\SecurityBundle\Test\Functional;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This trait can be used in functional tests where you need to change permissions for security roles.
 * It is expected that this trait will be used in classes
 * derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait RolePermissionExtension
{
    /**
     * @afterInitClient
     */
    protected function clearAclCache()
    {
        $container = self::getContainer();
        if (null !== $container && !self::isDbIsolationPerTest()) {
            $container->get('tests.security.acl.cache.doctrine')->clearCache();
        }
    }

    /**
     * Updates a permission for given entity for the given role.
     *
     * @param string $roleName
     * @param string $entityClass
     * @param int    $accessLevel
     * @param string $permission
     */
    protected function updateRolePermission(
        string $roleName,
        string $entityClass,
        int $accessLevel,
        string $permission = 'VIEW'
    ): void {
        $this->saveRolePermissions(
            $roleName,
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass),
            [$permission => $accessLevel]
        );
    }

    /**
     * Updates permissions for given entity for the given role.
     *
     * @param string $roleName
     * @param string $entityClass
     * @param array  $permissions [permission => access level, ...]
     */
    protected function updateRolePermissions(
        string $roleName,
        string $entityClass,
        array $permissions
    ): void {
        $this->saveRolePermissions(
            $roleName,
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass),
            $permissions
        );
    }

    /**
     * Updates a permission for given entity for the given role.
     *
     * @param string $roleName
     * @param string $entityClass
     * @param string $fieldName
     * @param int    $accessLevel
     * @param string $permission
     */
    protected function updateRolePermissionForField(
        string $roleName,
        string $entityClass,
        string $fieldName,
        int $accessLevel,
        string $permission = 'VIEW'
    ): void {
        $this->saveRolePermissions(
            $roleName,
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass),
            [$permission => $accessLevel],
            $fieldName
        );
    }

    /**
     * Updates permissions for given entity for the given role.
     *
     * @param string $roleName
     * @param string $entityClass
     * @param string $fieldName
     * @param array  $permissions [permission => access level, ...]
     */
    protected function updateRolePermissionsForField(
        string $roleName,
        string $entityClass,
        string $fieldName,
        array $permissions
    ): void {
        $this->saveRolePermissions(
            $roleName,
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass),
            $permissions,
            $fieldName
        );
    }

    /**
     * Updates a permission for given action for the given role.
     *
     * @param string $roleName
     * @param string $action
     * @param bool   $value
     */
    protected function updateRolePermissionForAction(
        string $roleName,
        string $action,
        bool $value
    ): void {
        $this->saveRolePermissions(
            $roleName,
            ObjectIdentityHelper::encodeIdentityString(ActionAclExtension::NAME, $action),
            ['EXECUTE' => $value]
        );
    }

    /**
     * @param string      $roleName
     * @param string      $objectIdentity
     * @param array       $permissions [permission => access level, ...]
     * @param string|null $fieldName
     */
    private function saveRolePermissions(
        string $roleName,
        string $objectIdentity,
        array $permissions,
        string $fieldName = null
    ): void {
        /** @var ContainerInterface $container */
        $container = self::getContainer();

        /** @var AclManager $aclManager */
        $aclManager = $container->get('oro_security.acl.manager');
        $aclExtension = $aclManager
            ->getExtensionSelector()
            ->selectByExtensionKey(ObjectIdentityHelper::getExtensionKeyFromIdentityString($objectIdentity));

        $sid = $aclManager->getSid($roleName);
        $oid = $aclManager->getOid($objectIdentity);

        $maskBuilders = $fieldName
            ? $aclExtension->getFieldExtension()->getAllMaskBuilders()
            : $aclExtension->getAllMaskBuilders();
        foreach ($maskBuilders as $maskBuilder) {
            $mask = $this->buildAclMask($maskBuilder, $permissions);
            if ($fieldName) {
                $aclManager->setFieldPermission($sid, $oid, $fieldName, $mask);
            } else {
                $aclManager->setPermission($sid, $oid, $mask);
            }
        }
        $aclManager->flush();

        if (!self::isDbIsolationPerTest()) {
            /** @var EntityManagerInterface $em */
            $em = $container->get('doctrine')->getManager();
            $cacheDriver = $em->getConfiguration()->getQueryCacheImpl();
            if ($cacheDriver && !($cacheDriver instanceof ApcCache && $cacheDriver instanceof XcacheCache)) {
                $cacheDriver->deleteAll();
            }
        }
    }

    /**
     * @param MaskBuilder $maskBuilder
     * @param array       $permissions
     *
     * @return int
     */
    private function buildAclMask(MaskBuilder $maskBuilder, array $permissions): int
    {
        $mask = $maskBuilder->reset()->get();
        foreach ($permissions as $permission => $accessLevel) {
            $maskName = null;
            if (is_int($accessLevel)) {
                $maskName = sprintf('%s_%s', $permission, AccessLevel::getAccessLevelName($accessLevel));
            } elseif (true === $accessLevel) {
                $maskName = $permission;
            }
            if (null !== $maskName && $maskBuilder->hasMask('MASK_' . $maskName)) {
                $mask = $maskBuilder->add($maskName)->get();
            }
        }

        return $mask;
    }
}
