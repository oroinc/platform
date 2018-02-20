<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadRoleData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
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
        $roleTest1 = new Role();
        $roleTest1->setLabel('Role 1');
        $roleTest1->setRole('ROLE_TEST_1');
        $manager->persist($roleTest1);
        $this->setReference('ROLE_TEST_1', $roleTest1);

        $roleTest2 = new Role();
        $roleTest2->setLabel('Role 2');
        $roleTest2->setRole('ROLE_TEST_2');
        $manager->persist($roleTest2);
        $this->setReference('ROLE_TEST_2', $roleTest2);
        $manager->flush();
    }
}
