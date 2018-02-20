<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    protected static $languages = ['en', 'en_CA', 'en_US', 'es', 'es_ES', 'es_MX'];

    /** @var array */
    protected static $localizations = [
        [
            'language' => 'en_US',
            'formatting' => 'en_US',
            'parent' => null,
            'title' => 'English (United States)',
        ],
        [
            'language' => 'en_CA',
            'formatting' => 'en_CA',
            'parent' => 'en_US',
            'title' => 'English (Canada)',
        ],
        [
            'language' => 'es',
            'formatting' => 'es',
            'parent' => null,
            'title' => 'Spanish',
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // Preload all required Languages
        foreach (self::$languages as $item) {
            $this->processLanguage($item, $manager);
        }

        /* @var $repository LocalizationRepository */
        $repository = $manager->getRepository(Localization::class);

        $registry = [];

        foreach (self::$localizations as $item) {
            $code = $item['language'];

            /** @var Language $language */
            $language = $this->getReference('language.' . $code);

            $localization = $repository->findOneBy(['language' => $language]);
            if (null === $localization) {
                $localization = new Localization();
                $localization
                    ->setLanguage($language)
                    ->setFormattingCode($item['formatting'])
                    ->setName($item['title'])
                    ->setDefaultTitle($item['title']);

                if ($item['parent']) {
                    $localization->setParentLocalization($registry[$item['parent']]);
                }

                $manager->persist($localization);
            }

            $registry[$code] = $localization;

            $this->addReference($code, $localization);
        }

        $manager->flush();
        $manager->clear();

        $this->updateEnabledLocalizations($manager);
    }

    /**
     * @param string $langCode
     * @param ObjectManager $manager
     */
    protected function processLanguage($langCode, ObjectManager $manager)
    {
        /* @var $repository LanguageRepository */
        $repository = $manager->getRepository(Language::class);
        $language = $repository->findOneBy(['code' => $langCode]);
        if (!$language) {
            $language = new Language();
            $language->setCode($langCode)->setEnabled(1);

            $manager->persist($language);
            $manager->flush($language);
        }

        $reference = 'language.' . $language->getCode();

        if (!$this->hasReference($reference)) {
            $this->addReference($reference, $language);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    protected function updateEnabledLocalizations(ObjectManager $manager)
    {
        /* @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_config.global');

        /* @var $localizations Localization[] */
        $localizations = $manager->getRepository(Localization::class)->findAll();

        $enabledLocalizations = [];

        foreach ($localizations as $localization) {
            $enabledLocalizations[] = $localization->getId();
        }

        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            $enabledLocalizations
        );

        $configManager->flush();
    }

    /**
     * @return array
     */
    public static function getLocalizations()
    {
        return self::$localizations;
    }
}
