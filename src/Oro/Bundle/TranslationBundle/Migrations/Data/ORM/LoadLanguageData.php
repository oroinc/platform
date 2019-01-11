<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migration for creation default `en` language
 */
class LoadLanguageData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAdminUserData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);

        /** Default language must be in list by default, because we already have translations in *.en.yml files */
        $this->getLanguage($manager, Translator::DEFAULT_LOCALE)
            ->setEnabled(true)
            ->setOwner($user)
            ->setOrganization($user->getOrganization());

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @return Language
     */
    protected function getLanguage(ObjectManager $manager, $code)
    {
        if (null === ($language = $manager->getRepository(Language::class)->findOneBy(['code' => $code]))) {
            $language = new Language();
            $language->setCode($code);
            $manager->persist($language);
        }

        return $language;
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws \RuntimeException
     *
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $manager->getRepository('OroUserBundle:Role')->getFirstMatchedUser($role);
        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
