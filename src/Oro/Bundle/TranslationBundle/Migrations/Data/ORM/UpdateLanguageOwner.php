<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

/**
 * Loads language owner data
 */
class UpdateLanguageOwner extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = $this->getUser($manager);
        /* @var Language[] $languages */
        $languages = $manager->getRepository(Language::class)->findAll();
        foreach ($languages as $language) {
            $language->setOrganization($user->getOrganization());
        }
        $manager->flush();
    }

    protected function getUser(ObjectManager $manager): User
    {
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $manager->getRepository(Role::class)->getFirstMatchedUser($role);
        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
