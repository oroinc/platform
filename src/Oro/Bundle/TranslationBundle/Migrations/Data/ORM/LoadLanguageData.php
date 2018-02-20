<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
        /* @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_config.global');

        $defaultLanguage = $configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGE));
        $enabledLanguages = (array)$configManager->get(Configuration::getConfigKeyByName('languages'));
        $downloadedLanguages = array_keys((array)$configManager->get('oro_translation.available_translations'));

        /** Default language must be in list by default, because we already have translations in *.en.yml files */
        $languages = array_unique(
            array_merge(
                [Translator::DEFAULT_LOCALE, $defaultLanguage],
                $enabledLanguages,
                $downloadedLanguages
            )
        );

        $configManager->set(
            Configuration::getConfigKeyByName('languages'),
            array_unique(array_merge($enabledLanguages, [Translator::DEFAULT_LOCALE, $defaultLanguage]))
        );
        $configManager->flush();

        $user = $this->getUser($manager);

        foreach ($languages as $languageCode) {
            $this->getLanguage($manager, $languageCode)
                ->setEnabled(in_array($languageCode, $enabledLanguages, true) ||
                    (($defaultLanguage === $languageCode) || (Translator::DEFAULT_LOCALE === $languageCode)))
                ->setOrganization($user->getOrganization())
                ->setOwner($user);
        }

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
