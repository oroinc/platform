<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class UpdateEmailEditAclRule extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            $manager->flush();
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function updateUserRole(AclManager $manager)
    {
        $role = $this->getRole(LoadRolesData::ROLE_ADMINISTRATOR);
        if ($role) {
            $sid = $manager->getSid($role);
            $oid = $manager->getOid('entity:Oro\Bundle\EmailBundle\Entity\Email');

            $extension = $manager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                foreach (['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM'] as $permission) {
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
     * @return Role|null
     */
    protected function getRole($roleName)
    {
        return $this->objectManager->getRepository('OroUserBundle:Role')->findOneBy(['role' => $roleName]);
    }
}
