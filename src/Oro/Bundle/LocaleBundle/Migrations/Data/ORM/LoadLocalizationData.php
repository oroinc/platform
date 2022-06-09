<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Intl\Locales;

/**
 * Creates localizations: default, en and the one specified during installation.
 */
class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
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
        /* @var ConfigManager $configManager */
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

    private function getLocalization(ObjectManager $manager, string $locale, Language $language): ?Localization
    {
        return $manager->getRepository(Localization::class)
            ->findOneBy(['language' => $language, 'formattingCode' => $locale]);
    }

    private function createLocalization(ObjectManager $manager, string $locale, string $languageCode): ?Localization
    {
        $language = $manager->getRepository(Language::class)->findOneBy([
            'code' => $languageCode,
            'enabled' => true,
        ]);
        if (!$language) {
            return null;
        }

        $localization = new Localization();
        $title = Locales::getName($locale, $locale);
        $localization->setLanguage($language)
            ->setFormattingCode($locale)
            ->setName($title)
            ->setDefaultTitle($title);
        $manager->persist($localization);
        $manager->flush($localization);

        return $localization;
    }

    private function getLocale(): string
    {
        /** @var LocaleSettings $localeSettings */
        $localeSettings = $this->container->get('oro_locale.settings');

        return $localeSettings->getLocale();
    }

    private function isSupportedLocale(string $locale): bool
    {
        return Locales::exists($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }
}
