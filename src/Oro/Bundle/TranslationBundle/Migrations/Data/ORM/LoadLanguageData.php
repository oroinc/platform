<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class LoadLanguageData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganizationAndBusinessUnitData::class
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
        $enabledLanguages = $configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGES));
        $downloadedLanguages = array_keys($configManager->get(TranslationStatusInterface::CONFIG_KEY));

        $languages = array_unique(array_merge([$defaultLanguage], $enabledLanguages, $downloadedLanguages));
        $organization = $this->getOrganization($manager);

        foreach ($languages as $languageCode) {
            $language = new Language();
            $language->setCode($languageCode)
                ->setEnabled(in_array($languageCode, $enabledLanguages, true) || $defaultLanguage === $languageCode)
                ->setOrganization($organization);

            $manager->persist($language);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Organization
     */
    protected function getOrganization(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroOrganizationBundle:Organization');

        $organization = $repository->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION]);

        if (!$organization) {
            $organization = $repository->getFirst();
        }

        if (!$organization) {
            throw new \RuntimeException('At least ane organization should be exist.');
        }

        return $organization;
    }
}
