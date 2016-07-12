<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface
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

        $language = $this->getLanguageCode($locale);
        if (!$this->isSupportedLanguage($language)) {
            throw new \LogicException(sprintf('There are no language with code "%s"!', $language));
        }

        $localization = $this->getDefaultLocalization($manager, $locale, $language);
        if (!$localization) {
            $localization = new Localization();
            $localization->setLanguageCode($language)->setFormattingCode($locale);

            $manager->persist($localization);
        }

        $title = Intl::getLocaleBundle()->getLocaleName($locale, $locale);
        $localization->setName($title)->setDefaultTitle($title);

        $manager->flush();

        $this->addReference('default_localization', $localization);
        $this->setSystemDefaultLocalization($localization);
    }

    /**
     * @param ObjectManager $manager
     * @param string $locale
     * @param string $language
     * @return Localization
     */
    protected function getDefaultLocalization(ObjectManager $manager, $locale, $language)
    {
        return $manager->getRepository('OroLocaleBundle:Localization')
            ->findOneBy(
                [
                    'languageCode' => $language,
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
     * @return string
     */
    protected function getLanguageCode($locale)
    {
        list($language) = explode('_', $locale);

        return $language;
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
     * @param Localization $localization
     */
    protected function setSystemDefaultLocalization(Localization $localization)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.manager');
        $configManager->set('oro_locale.' . Configuration::DEFAULT_LOCALIZATION, $localization->getId());
        $configManager->flush();
    }
}
