<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTranslationUsers extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    const TRANSLATOR_USERNAME = 'translator';
    const TRANSLATOR_EMAIL = 'translator@example.com';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadTranslationRoles::class, LoadBusinessUnit::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadTranslator($manager, $this->container->get('oro_user.manager'));
    }

    public function loadTranslator(ObjectManager $manager, UserManager $userManager)
    {
        $role = $manager->getRepository(Role::class)
            ->findOneBy(['role' => LoadTranslationRoles::ROLE_TRANSLATOR]);

        $organization = $manager->getRepository(Organization::class)->findOneBy([]);

        /* @var User $user */
        $user = $userManager->createUser();
        $user
            ->setOwner($this->getReference('business_unit'))
            ->setFirstName('Demo')
            ->setLastName('Translator')
            ->setEmail(self::TRANSLATOR_EMAIL)
            ->setPlainPassword(self::TRANSLATOR_USERNAME)
            ->addUserRole($role)
            ->setEnabled(true)
            ->setUsername(self::TRANSLATOR_USERNAME)
            ->setOrganization($organization)
            ->addOrganization($organization);

        $userManager->updateUser($user);
    }
}
