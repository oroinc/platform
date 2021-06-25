<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const SIMPLE_USER = 'simple_user';
    const SIMPLE_USER_FIRST_NAME = 'Elley';
    const SIMPLE_USER_LAST_NAME = 'Towards';
    const SIMPLE_USER_EMAIL = 'simple_user@example.com';
    const SIMPLE_USER_PASSWORD = 'simple_password';

    const SIMPLE_USER_2 = 'simple_user2';
    const SIMPLE_USER_2_FIRST_NAME = 'Merry';
    const SIMPLE_USER_2_LAST_NAME = 'Backwards';
    const SIMPLE_USER_2_EMAIL = 'simple_user2@example.com';
    const SIMPLE_USER_2_PASSWORD = 'simple_password2';

    const USER_WITH_CONFIRMATION_TOKEN = 'user_with_confirmation_token';
    const USER_WITH_CONFIRMATION_TOKEN_FIRST_NAME = 'Forgot';
    const USER_WITH_CONFIRMATION_TOKEN_LAST_NAME = 'Password';
    const USER_WITH_CONFIRMATION_TOKEN_EMAIL = 'user_with_confirmation_token@example.com';
    const USER_WITH_CONFIRMATION_TOKEN_PASSWORD = 'simple_password3';

    const CONFIRMATION_TOKEN = 'confirmation_token';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadOrganization::class, LoadBusinessUnit::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $this->getReference('organization');
        $businessUnit = $this->getReference('business_unit');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $user = $userManager->createUser();
        $user->setUsername(self::SIMPLE_USER)
            ->setOwner($businessUnit)
            ->setPlainPassword(self::SIMPLE_USER_PASSWORD)
            ->setEmail(self::SIMPLE_USER_EMAIL)
            ->setFirstName(self::SIMPLE_USER_FIRST_NAME)
            ->setLastName(self::SIMPLE_USER_LAST_NAME)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername(self::SIMPLE_USER_2)
            ->setOwner($businessUnit)
            ->setPlainPassword(self::SIMPLE_USER_2_PASSWORD)
            ->setFirstName(self::SIMPLE_USER_2_FIRST_NAME)
            ->setLastName(self::SIMPLE_USER_2_LAST_NAME)
            ->setEmail(self::SIMPLE_USER_2_EMAIL)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user2);

        $userWithToken = $userManager->createUser();
        $userWithToken->setUsername(self::USER_WITH_CONFIRMATION_TOKEN)
            ->setOwner($businessUnit)
            ->setPlainPassword(self::USER_WITH_CONFIRMATION_TOKEN_PASSWORD)
            ->setFirstName(self::USER_WITH_CONFIRMATION_TOKEN_FIRST_NAME)
            ->setLastName(self::USER_WITH_CONFIRMATION_TOKEN_LAST_NAME)
            ->setEmail(self::USER_WITH_CONFIRMATION_TOKEN_EMAIL)
            ->setOrganization($organization)
            ->setConfirmationToken(self::CONFIRMATION_TOKEN)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($userWithToken);

        $this->setReference(self::SIMPLE_USER, $user);
        $this->setReference(self::SIMPLE_USER_2, $user2);
        $this->setReference(self::USER_WITH_CONFIRMATION_TOKEN, $userWithToken);
    }
}
