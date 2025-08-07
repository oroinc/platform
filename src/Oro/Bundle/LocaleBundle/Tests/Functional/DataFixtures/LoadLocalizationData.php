<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Psr\Container\ContainerInterface;

class LoadLocalizationData extends AbstractFixture
{
    public const DEFAULT_LOCALIZATION_CODE = 'en_US';
    public const EN_CA_LOCALIZATION_CODE = 'en_CA';
    public const ES_LOCALIZATION_CODE = 'es';

    private static array $languages = ['en', 'en_CA', 'es', 'es_ES', 'es_MX'];
    private static array $localizations = [
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

    public static function getLocalizations(): array
    {
        return self::$localizations;
    }

    public static function getLocalizationIds(ContainerInterface $container): array
    {
        return array_map(function (Localization $localization) {
            return $localization->getId();
        }, $container->get('doctrine')->getRepository(Localization::class)->findAll());
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // Preload all required Languages
        foreach (self::$languages as $item) {
            $this->processLanguage($item, $manager);
        }

        /* @var LocalizationRepository $repository */
        $repository = $manager->getRepository(Localization::class);
        $defaultEnUsLocalization = $repository->findOneBy(['formattingCode' => self::DEFAULT_LOCALIZATION_CODE]);
        if (!$defaultEnUsLocalization) {
            throw new \LogicException(
                'No default localization found in the system with formatting code - ' . self::DEFAULT_LOCALIZATION_CODE
            );
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
                $localization->setLanguage($language);
                $localization->setFormattingCode($item['formatting']);
                $localization->setName($item['title']);
                $localization->setDefaultTitle($item['title']);
                if ($item['parent']) {
                    $localization->setParentLocalization($registry[$item['parent']]);
                }
                $manager->persist($localization);
            }
            $this->addReference($code, $localization);
            $registry[$code] = $localization;
        }
        $manager->flush();
    }

    private function processLanguage(string $langCode, ObjectManager $manager): void
    {
        /* @var LanguageRepository $repository */
        $repository = $manager->getRepository(Language::class);
        $language = $repository->findOneBy(['code' => $langCode]);
        if (!$language) {
            $language = new Language();
            $language->setCode($langCode);
            $language->setEnabled(true);
            $manager->persist($language);
            $manager->flush($language);
        }
        $reference = 'language.' . $language->getCode();
        if (!$this->hasReference($reference)) {
            $this->addReference($reference, $language);
        }
    }
}
