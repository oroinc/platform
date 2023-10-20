<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailSuggestionData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadEmailActivityData::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->getUserManager();

        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $email = new Email();
        $email->setEmail('test1@example.com');

        $manager->persist($email);

        $user10 = $this->createUser($userManager, $organization, 'Lucas', 'Thornton', $email);
        $user11 = $this->createUser($userManager, $organization, 'Traci', 'Patric', $email);

        $this->setReference('user_10', $user10);
        $this->setReference('user_11', $user11);

        $manager->flush();
    }

    private function createUser(
        UserManager $userManager,
        Organization $organization,
        string $firstName,
        string $lastName,
        Email $email
    ): User {
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setOrganization($organization);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername(strtolower($firstName . '.' . $lastName));
        $user->setPassword(strtolower($firstName . '.' . $lastName));
        $user->setEmail(strtolower($firstName . '_' . $lastName . '@example.com'));
        $user->addEmail($email);

        $userManager->updateUser($user);

        return $user;
    }

    private function getUserManager(): UserManager
    {
        return $this->container->get('oro_user.manager');
    }
}
