<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;

class AddProfileAndConfigUpdateCabalitiesToRoles extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
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

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        if ($aclManager->isAclEnabled()) {
            $this->updateUserRole($aclManager);
            $aclManager->flush();
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function updateUserRole(AclManager $manager)
    {
        $acls = ['EXECUTE'];
        $actions = ['update_own_profile', 'update_own_configuration'];
        $roles = $this->getRoles();
        foreach ($roles as $role) {
            $sid = $manager->getSid($role);
            foreach ($actions as $action) {
                $oid = $manager->getOid('action:' . $action);
                $extension = $manager->getExtensionSelector()->select($oid);
                $maskBuilders = $extension->getAllMaskBuilders();

                foreach ($maskBuilders as $maskBuilder) {
                    $mask = $maskBuilder->reset()->get();

                    foreach ($acls as $acl) {
                        if ($maskBuilder->hasMask('MASK_' . $acl)) {
                            $mask = $maskBuilder->add($acl)->get();
                        }
                    }

                    $manager->setPermission($sid, $oid, $mask);
                }
            }
        }
    }

    /**
     * @return Role[]
     */
    protected function getRoles()
    {
        return $this->objectManager->getRepository('OroUserBundle:Role')->findAll();
    }
}
