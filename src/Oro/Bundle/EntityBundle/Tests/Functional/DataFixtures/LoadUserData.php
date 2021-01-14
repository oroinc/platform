<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
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
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $status1 = new Status();
        $status1->setStatus('status1');
        $manager->persist($status1);
        $this->addReference('status1', $status1);

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization)
            ->setOrganizations(new ArrayCollection([$organization]))
            ->setOwner($organization->getBusinessUnits()->first())
            ->addRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
