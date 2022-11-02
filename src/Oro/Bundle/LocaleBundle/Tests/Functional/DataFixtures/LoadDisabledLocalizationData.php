<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds test localization
 */
class LoadDisabledLocalizationData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    protected static $localizations = [
        [
            'language' => 'es_MX',
            'formatting' => 'es_MX',
            'parent' => 'es',
            'title' => 'Mexican Spanish',
        ]
    ];

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
                $localization
                    ->setLanguage($language)
                    ->setFormattingCode($item['formatting'])
                    ->setName($item['title'])
                    ->setDefaultTitle($item['title']);

                if ($item['parent']) {
                    $parentLocalization = $repository->findOneBy(['formattingCode' => $item['parent']]);
                    if (!$parentLocalization) {
                        throw new LogicException('No parent localization found in the system with formatting code - '
                            . $item['parent']);
                    }
                    $localization->setParentLocalization($parentLocalization);
                }

                $manager->persist($localization);
            }

            $this->addReference($code, $localization);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @return array
     */
    public static function getLocalizations(): array
    {
        return self::$localizations;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadLocalizationData::class];
    }
}
