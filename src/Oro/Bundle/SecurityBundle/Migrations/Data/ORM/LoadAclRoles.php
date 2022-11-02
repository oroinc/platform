<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets default permissions for the following roles:
 * * ROLE_ADMINISTRATOR
 * * ROLE_MANAGER
 * * ROLE_USER
 */
class LoadAclRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadRolesData::class];
    }

    public function load(ObjectManager $manager)
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $this->setPermissionsForAdminRole($aclManager, $this->getRole($manager, LoadRolesData::ROLE_ADMINISTRATOR));
        $this->setPermissionsForManagerRole($aclManager, $this->getRole($manager, LoadRolesData::ROLE_MANAGER));
        $this->setPermissionsForUserRole($aclManager, $this->getRole($manager, LoadRolesData::ROLE_USER));
        $aclManager->flush();
    }

    private function setPermissionsForAdminRole(AclManager $aclManager, Role $role)
    {
        $sid = $aclManager->getSid($role);
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $mask = $maskBuilder->hasMaskForGroup('SYSTEM')
                    ? $maskBuilder->getMaskForGroup('SYSTEM')
                    : $maskBuilder->getMaskForGroup('ALL');
                $aclManager->setPermission($sid, $rootOid, $mask, true);
            }
        }
    }

    private function setPermissionsForManagerRole(AclManager $aclManager, Role $role)
    {
        $sid = $aclManager->getSid($role);
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $mask = $maskBuilder->hasMaskForPermission('VIEW_SYSTEM')
                    ? $maskBuilder->getMaskForPermission('VIEW_SYSTEM')
                    : $maskBuilder->getMaskForGroup('NONE');
                $aclManager->setPermission($sid, $rootOid, $mask, true);
            }
        }
    }

    private function setPermissionsForUserRole(AclManager $aclManager, Role $role)
    {
        $sid = $aclManager->getSid($role);
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $mask = $maskBuilder->hasMaskForPermission('VIEW_SYSTEM')
                    ? $maskBuilder->getMaskForPermission('VIEW_SYSTEM')
                    : $maskBuilder->getMaskForGroup('NONE');
                $aclManager->setPermission($sid, $rootOid, $mask, true);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string        $roleName
     *
     * @return Role
     */
    private function getRole(ObjectManager $manager, string $roleName)
    {
        return $manager->getRepository(Role::class)->findOneBy(['role' => $roleName]);
    }
}
