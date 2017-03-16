<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;

class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locale = $this->getLocale();
        if (!$this->isSupportedLocale($locale)) {
            throw new \LogicException(sprintf('There are no locale with code "%s"!', $locale));
        }

        $language = $manager->getRepository(Language::class)->findOneBy(['code' => $locale]);
        $localization = $this->getDefaultLocalization($manager, $locale, $language);
        if (!$localization) {
            $localization = new Localization();
            $localization->setLanguage($language)->setFormattingCode($locale);

            $manager->persist($localization);
        }

        $title = Intl::getLocaleBundle()->getLocaleName($locale, $locale);
        $localization->setName($title)->setDefaultTitle($title);

        $manager->flush();

        /* @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_config.global');

        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            [$localization->getId()]
        );
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
            $localization->getId()
        );
        $configManager->flush();

        $this->addReference('default_localization', $localization);
    }

    /**
     * @param ObjectManager $manager
     * @param string $locale
     * @param Language $language
     * @return Localization
     */
    protected function getDefaultLocalization(ObjectManager $manager, $locale, Language $language)
    {
        return $manager->getRepository('OroLocaleBundle:Localization')
            ->findOneBy(
                [
                    'language' => $language,
                    'formattingCode' => $locale
                ]
            );
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        $localeSettings = $this->container->get('oro_locale.settings');

        return $localeSettings->getLocale();
    }

    /**
     * @param string $locale
     * @return bool
     */
    protected function isSupportedLocale($locale)
    {
        $locales = Intl::getLocaleBundle()->getLocaleNames();

        return array_key_exists($locale, $locales);
    }

    /**
     * @param string $language
     * @return bool
     */
    protected function isSupportedLanguage($language)
    {
        $languages = Intl::getLanguageBundle()->getLanguageNames();

        return array_key_exists($language, $languages);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadLanguageData::class];
    }
}
