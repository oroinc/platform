<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads default "en" language.
 */
class LoadLanguageData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAdminUserData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = $this->getUser($manager);

        /** Default language must be in list by default, because we already have translations in *.en.yml files */
        $this->getLanguage($manager, Translator::DEFAULT_LOCALE)
            ->setEnabled(true)
            ->setOrganization($user->getOrganization());

        $manager->flush();
    }

    private function getLanguage(ObjectManager $manager, string $code): Language
    {
        $language = $manager->getRepository(Language::class)->findOneBy(['code' => $code]);
        if (null === $language) {
            $language = new Language();
            $language->setCode($code);
            $manager->persist($language);
        }

        return $language;
    }

    private function getUser(ObjectManager $manager): User
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
