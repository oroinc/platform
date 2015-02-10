<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

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

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setEnabled(true);

        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
