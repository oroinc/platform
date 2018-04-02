<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    const SIMPLE_USER_ENABLED = 'simple_user_enabled';
    const SIMPLE_USER_ENABLED_PASSWORD = 'simple_user_enabled';
    const SIMPLE_USER_DISABLED = 'simple_user_disabled';
    const SIMPLE_USER_DISABLED_PASSWORD = 'simple_user_disabled';

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
        $user->setUsername(self::SIMPLE_USER_ENABLED)
            ->setPlainPassword(self::SIMPLE_USER_ENABLED_PASSWORD)
            ->setEmail('simple_user@example.com')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername(self::SIMPLE_USER_DISABLED)
            ->setPlainPassword(self::SIMPLE_USER_DISABLED_PASSWORD)
            ->setFirstName('Merry')
            ->setLastName('Backwards')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(false);

        $userManager->updateUser($user2);

        $this->setReference(self::SIMPLE_USER_ENABLED, $user);
        $this->setReference(self::SIMPLE_USER_DISABLED, $user2);
    }
}
