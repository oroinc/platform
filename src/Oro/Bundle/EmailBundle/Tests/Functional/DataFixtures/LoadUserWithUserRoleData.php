<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserWithUserRoleData extends AbstractFixture implements ContainerAwareInterface
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
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_USER']);

        $user = $userManager->createUser();
        $user
            ->setUsername('limited_user')
            ->setEmail('limited_user@test.com')
            ->setPlainPassword('limited_user')
            ->addRole($role)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEnabled(true)
            ->setSalt('');
        $apiKey = new UserApi();
        $apiKey->setApiKey('limited_user');
        $apiKey->setOrganization($organization);
        $manager->persist($apiKey);
        $user->addApiKey($apiKey);

        $userManager->updateUser($user);
        $manager->flush();

        $this->setReference($user->getUsername(), $user);
    }
}
