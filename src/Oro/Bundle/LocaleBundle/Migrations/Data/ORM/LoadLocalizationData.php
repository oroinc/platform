<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Intl\Intl;

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

        if (Translator::DEFAULT_LOCALE !== $locale) {
            // Creating Default Localization For default Language
            $language = $manager->getRepository(Language::class)->findOneBy([
                'code' => Translator::DEFAULT_LOCALE
            ]);
            $enLocalization = $this->getLocalization(
                $manager,
                Translator::DEFAULT_LOCALE,
                $language
            );
            if (!$enLocalization) {
                $enLocalization = $this->createLocalization(
                    $manager,
                    Translator::DEFAULT_LOCALE,
                    $language->getCode()
                );
                $manager->persist($enLocalization);
            }
        }

        $language = $manager->getRepository(Language::class)->findOneBy(['code' => $locale]);
        $localization = $this->getLocalization($manager, $locale, $language);
        if (!$localization) {
            $localization = $this->createLocalization($manager, $locale, $language->getCode());
            $manager->persist($localization);
        }

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
     *
     * @return Localization
     */
    protected function getLocalization(ObjectManager $manager, $locale, Language $language)
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
     * @param ObjectManager $manager
     * @param string $locale
     * @param string $languageCode
     *
     * @return null|Localization
     */
    protected function createLocalization(ObjectManager $manager, $locale, $languageCode)
    {
        $language = $manager->getRepository(Language::class)->findOneBy([
            'code' => $languageCode,
            'enabled' => true,
        ]);
        if (!$language) {
            return null;
        }

        $localization = new Localization();
        $title = Intl::getLocaleBundle()->getLocaleName($locale, $locale);
        $localization->setLanguage($language)
            ->setFormattingCode($locale)
            ->setName($title)
            ->setDefaultTitle($title);
        $manager->persist($localization);
        $manager->flush($localization);

        return $localization;
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
     *
     * @return bool
     */
    protected function isSupportedLocale($locale)
    {
        $locales = Intl::getLocaleBundle()->getLocaleNames();

        return array_key_exists($locale, $locales);
    }

    /**
     * @param string $language
     *
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
