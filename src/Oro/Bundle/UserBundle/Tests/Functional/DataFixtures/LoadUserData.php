<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    const SIMPLE_USER = 'simple_user';
    const SIMPLE_USER_2 = 'simple_user2';
    const USER_WITH_CONFIRMATION_TOKEN = 'user_with_confirmation_token';

    const CONFIRMATION_TOKEN = 'confirmation_token';

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
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername(self::SIMPLE_USER_2)
            ->setPlainPassword('simple_password2')
            ->setFirstName('Merry')
            ->setLastName('Backwards')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($user2);

        $userWithToken = $userManager->createUser();
        $userWithToken->setUsername(self::USER_WITH_CONFIRMATION_TOKEN)
            ->setPlainPassword('simple_password3')
            ->setFirstName('Forgot')
            ->setLastName('Password')
            ->setEmail('user_with_confirmation_token@example.com')
            ->setOrganization($organization)
            ->setConfirmationToken(self::CONFIRMATION_TOKEN)
            ->addOrganization($organization)
            ->setEnabled(true);

        $userManager->updateUser($userWithToken);

        $this->setReference(self::SIMPLE_USER, $user);
        $this->setReference(self::SIMPLE_USER_2, $user2);
        $this->setReference(self::USER_WITH_CONFIRMATION_TOKEN, $userWithToken);
    }
}
