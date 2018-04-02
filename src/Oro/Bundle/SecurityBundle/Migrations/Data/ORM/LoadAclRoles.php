<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadAclRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData'];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load ACL for security roles
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->objectManager = $manager;

        /** @var AclManager $manager */
        $manager = $this->container->get('oro_security.acl.manager');

        if ($manager->isAclEnabled()) {
            $this->loadSuperAdminRole($manager);
            $this->loadManagerRole($manager);
            $this->loadUserRole($manager);
            $manager->flush();
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function loadSuperAdminRole(AclManager $manager)
    {
        $sid = $manager->getSid($this->getRole(LoadRolesData::ROLE_ADMINISTRATOR));

        foreach ($manager->getAllExtensions() as $extension) {
            $rootOid = $manager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $fullAccessMask = $maskBuilder->hasMask('GROUP_SYSTEM')
                    ? $maskBuilder->getMask('GROUP_SYSTEM')
                    : $maskBuilder->getMask('GROUP_ALL');
                $manager->setPermission($sid, $rootOid, $fullAccessMask, true);
            }
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function loadManagerRole(AclManager $manager)
    {
        $sid = $manager->getSid($this->getRole(LoadRolesData::ROLE_MANAGER));

        foreach ($manager->getAllExtensions() as $extension) {
            $rootOid = $manager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                if ($maskBuilder->hasMask('MASK_VIEW_SYSTEM')) {
                    $mask = $maskBuilder->getMask('MASK_VIEW_SYSTEM');
                } else {
                    $mask = $maskBuilder->getMask('GROUP_NONE');
                }
                $manager->setPermission($sid, $rootOid, $mask, true);
            }
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function loadUserRole(AclManager $manager)
    {
        $sid = $manager->getSid($this->getRole(LoadRolesData::ROLE_USER));

        foreach ($manager->getAllExtensions() as $extension) {
            $rootOid = $manager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                if ($maskBuilder->hasMask('MASK_VIEW_SYSTEM')) {
                    $mask = $maskBuilder->getMask('MASK_VIEW_SYSTEM');
                } else {
                    $mask = $maskBuilder->getMask('GROUP_NONE');
                }
                $manager->setPermission($sid, $rootOid, $mask, true);
            }
        }
    }

    /**
     * @param string $roleName
     * @return Role
     */
    protected function getRole($roleName)
    {
        return $this->objectManager->getRepository('OroUserBundle:Role')->findOneBy(['role' => $roleName]);
    }
}
