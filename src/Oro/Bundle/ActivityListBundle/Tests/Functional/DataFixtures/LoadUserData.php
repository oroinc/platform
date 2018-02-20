<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function load(ObjectManager $manager)
    {
        $this->initUserPermissions($manager);
        $manager->flush();
    }

    protected function initUserPermissions(ObjectManager $manager)
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $roleAdmin = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
        $sid = $aclManager->getSid($roleAdmin);
        $oid = $aclManager->getOid('entity:Oro\Bundle\TestFrameworkBundle\Entity\TestActivity');
        $maskBuilder = $aclManager->getMaskBuilder($oid)
            ->reset();
        $aclManager->setPermission($sid, $oid, $maskBuilder->get());
    }
}
