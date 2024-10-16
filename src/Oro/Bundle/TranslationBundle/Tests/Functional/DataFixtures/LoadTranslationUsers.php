<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTranslationUsers extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const TRANSLATOR_USERNAME = 'translator';
    public const TRANSLATOR_EMAIL = 'translator@example.com';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadTranslationRoles::class, LoadBusinessUnit::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => LoadTranslationRoles::ROLE_TRANSLATOR]);
        /* @var User $user */
        $user = $userManager->createUser();
        $user
            ->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT))
            ->setFirstName('Demo')
            ->setLastName('Translator')
            ->setEmail(self::TRANSLATOR_EMAIL)
            ->setPlainPassword(self::TRANSLATOR_USERNAME)
            ->addUserRole($role)
            ->setEnabled(true)
            ->setUsername(self::TRANSLATOR_USERNAME)
            ->setOrganization($this->getReference(LoadOrganization::ORGANIZATION))
            ->addOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $userManager->updateUser($user);
    }
}
