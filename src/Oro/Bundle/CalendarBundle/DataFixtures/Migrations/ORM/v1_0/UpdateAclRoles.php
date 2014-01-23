<?php

namespace Oro\Bundle\CalendarBundle\DataFixtures\Migrations\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

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
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\SecurityBundle\DataFixtures\Migrations\ORM\v1_0\LoadAclRoles'];
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
        $sid = $manager->getSid($this->getRole('ROLE_USER'));

        // deny to view other user's calendar
        $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarConnection');
        $maskBuilder = $manager->getMaskBuilder($oid);
        $manager->setPermission($sid, $oid, $maskBuilder->get());

        // grant to manage own calendar events
        $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $maskBuilder = $manager->getMaskBuilder($oid)
            // ->add('VIEW_BASIC')
            // ->add('CREATE_BASIC')
            // ->add('EDIT_BASIC')
            // ->add('DELETE_BASIC');
            // @todo now only SYSTEM level is supported
            ->add('VIEW_SYSTEM')
            ->add('CREATE_SYSTEM')
            ->add('EDIT_SYSTEM')
            ->add('DELETE_SYSTEM');
        $manager->setPermission($sid, $oid, $maskBuilder->get());
    }

    protected function updateManagerRole(AclManager $manager)
    {
        $sid = $manager->getSid($this->getRole('ROLE_MANAGER'));

        // grant to view other user's calendar for the same business unit
        $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarConnection');
        $maskBuilder = $manager->getMaskBuilder($oid)
        //    ->add('VIEW_LOCAL');
        // @todo now only SYSTEM level is supported
            ->add('VIEW_SYSTEM');
        $manager->setPermission($sid, $oid, $maskBuilder->get());

        // grant to manage own calendar events
        $oid = $manager->getOid('entity:Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $maskBuilder = $manager->getMaskBuilder($oid)
            // ->add('VIEW_BASIC')
            // ->add('CREATE_BASIC')
            // ->add('EDIT_BASIC')
            // ->add('DELETE_BASIC');
            // @todo now only SYSTEM level is supported
            ->add('VIEW_SYSTEM')
            ->add('CREATE_SYSTEM')
            ->add('EDIT_SYSTEM')
            ->add('DELETE_SYSTEM');
        $manager->setPermission($sid, $oid, $maskBuilder->get());
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
