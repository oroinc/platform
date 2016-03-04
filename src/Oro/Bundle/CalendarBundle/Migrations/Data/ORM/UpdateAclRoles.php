<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class UpdateAclRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
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
        return ['Oro\Bundle\SecurityBundle\Migrations\Data\ORM\LoadAclRoles'];
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
            $this->updateUserRole($manager);
            $this->updateManagerRole($manager);
            $manager->flush();
        }
    }

    protected function updateUserRole(AclManager $manager)
    {
        $role = $this->getRole(LoadRolesData::ROLE_USER);

        if ($role) {
            $sid = $manager->getSid($role);

            // grant to manage own calendar events
            $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
            $extension = $manager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                // ->add('VIEW_BASIC')
                // ->add('CREATE_BASIC')
                // ->add('EDIT_BASIC')
                // ->add('DELETE_BASIC');
                // @todo now only SYSTEM level is supported
                foreach (['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'] as $permission) {
                    if ($maskBuilder->hasMask('MASK_' . $permission)) {
                        $maskBuilder->add($permission);
                    }
                }

                $manager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }

    protected function updateManagerRole(AclManager $manager)
    {
        $role = $this->getRole(LoadRolesData::ROLE_MANAGER);

        if ($role) {
            $sid = $manager->getSid($role);

            // grant to manage own calendar events
            $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
            $extension = $manager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                // ->add('VIEW_BASIC')
                // ->add('CREATE_BASIC')
                // ->add('EDIT_BASIC')
                // ->add('DELETE_BASIC');
                // @todo now only SYSTEM level is supported
                foreach (['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'] as $permission) {
                    if ($maskBuilder->hasMask('MASK_' . $permission)) {
                        $maskBuilder->add($permission);
                    }
                }

                $manager->setPermission($sid, $oid, $maskBuilder->get());
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
