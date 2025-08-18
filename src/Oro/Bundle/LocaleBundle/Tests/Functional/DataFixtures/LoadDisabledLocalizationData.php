<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Psr\Container\ContainerInterface;

class LoadDisabledLocalizationData extends AbstractFixture
{
    private static array $localizations = [
        [
            'language' => 'es_MX',
            'formatting' => 'es_MX',
            'parent' => 'es',
            'title' => 'Mexican Spanish',
        ]
    ];

    public static function getLocalizations(): array
    {
        return self::$localizations;
    }

    public static function getLocalizationIds(ContainerInterface $container): array
    {
        $languages = array_map(function (array $localization) {
            return $localization['language'];
        }, self::$localizations);
        $languageIds = array_map(function (Language $language) {
            return $language->getId();
        }, $container->get('doctrine')->getRepository(Language::class)->findBy(['code' => $languages]));

        return array_map(function (Localization $localization) {
            return $localization->getId();
        }, $container->get('doctrine')->getRepository(Localization::class)->findBy(['language' => $languageIds]));
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /* @var LocalizationRepository $repository */
        $repository = $manager->getRepository(Localization::class);
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
                    $parentLocalization = $repository->findOneBy(['formattingCode' => $item['parent']]);
                    if (!$parentLocalization) {
                        throw new LogicException(
                            'No parent localization found in the system with formatting code - ' . $item['parent']
                        );
                    }
                    $localization->setParentLocalization($parentLocalization);
                }
                $manager->persist($localization);
            }
            $this->addReference($code, $localization);
        }
        $manager->flush();
    }
}
