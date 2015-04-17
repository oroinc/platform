<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

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
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername('simple_user2')
            ->setPlainPassword('simple_password2')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user2);

        $this->setReference($user->getUsername(), $user);
        $this->setReference($user2->getUsername(), $user2);
    }
}
