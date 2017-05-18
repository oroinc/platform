<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    const SIMPLE_USER = 'simple_user';
    const SIMPLE_USER_PASSWORD = 'simple_password';

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
        $user->setUsername(self::SIMPLE_USER)
            ->setPlainPassword(self::SIMPLE_USER_PASSWORD)
            ->setEmail('simple_user@example.com')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
