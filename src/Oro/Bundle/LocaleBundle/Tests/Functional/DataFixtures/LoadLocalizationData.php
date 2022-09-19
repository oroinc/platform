<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds test localization
 */
class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface
{
    public const DEFAULT_LOCALIZATION_CODE = 'en_US';
    public const EN_CA_LOCALIZATION_CODE = 'en_CA';
    public const ES_LOCALIZATION_CODE = 'es';

    use ContainerAwareTrait;

    /** @var array */
    protected static $languages = ['en', 'en_CA', 'es', 'es_ES', 'es_MX'];

    /** @var array */
    protected static $localizations = [
        [
            'language' => self::EN_CA_LOCALIZATION_CODE,
            'formatting' => 'en_CA',
            'parent' => self::DEFAULT_LOCALIZATION_CODE,
            'title' => 'English (Canada)',
        ],
        [
            'language' => self::ES_LOCALIZATION_CODE,
            'formatting' => 'es',
            'parent' => null,
            'title' => 'Spanish',
        ]
    ];

    public function load(ObjectManager $manager)
    {
        // Preload all required Languages
        foreach (self::$languages as $item) {
            $this->processLanguage($item, $manager);
        }

        /* @var LocalizationRepository $repository */
        $repository = $manager->getRepository(Localization::class);
        $defaultEnUsLocalization = $repository->findOneBy(['formattingCode' => self::DEFAULT_LOCALIZATION_CODE]);
        if (!$defaultEnUsLocalization) {
            throw new \LogicException('No default localization found in the system with formatting code - '
                . self::DEFAULT_LOCALIZATION_CODE);
        }
        $this->addReference(self::DEFAULT_LOCALIZATION_CODE, $defaultEnUsLocalization);

        $registry[self::DEFAULT_LOCALIZATION_CODE] = $defaultEnUsLocalization;

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
        /* @var LanguageRepository $repository */
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

    protected function updateEnabledLocalizations(ObjectManager $manager)
    {
        /* @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');

        /* @var Localization[] $localizations */
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
