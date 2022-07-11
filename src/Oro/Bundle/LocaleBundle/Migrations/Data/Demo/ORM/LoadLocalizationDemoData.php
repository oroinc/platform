<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Migrations\Data\Demo\ORM\LoadLanguageDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Locales;

/**
 * Creates demo data for localisations.
 */
class LoadLocalizationDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private array $localizations = [
        ['language' => LoadLanguageDemoData::LANG_EN_US, 'formatting' => 'en_US', 'parent' => null],
        ['language' => LoadLanguageDemoData::LANG_EN_CA, 'formatting' => 'en_CA', 'parent' => 'en_US'],
        ['language' => LoadLanguageDemoData::LANG_EN_GB, 'formatting' => 'en_GB', 'parent' => 'en_US'],
        ['language' => LoadLanguageDemoData::LANG_EN_AU, 'formatting' => 'en_AU', 'parent' => 'en_US'],
        ['language' => LoadLanguageDemoData::LANG_ES_AR, 'formatting' => 'es_AR', 'parent' => 'en_US'],
        ['language' => LoadLanguageDemoData::LANG_FR_CA, 'formatting' => 'fr_CA', 'parent' => 'en_CA'],
        ['language' => LoadLanguageDemoData::LANG_FR_FR, 'formatting' => 'fr_FR', 'parent' => 'fr_CA'],
        ['language' => LoadLanguageDemoData::LANG_DE_DE, 'formatting' => 'de_DE', 'parent' => 'en_US'],
    ];

    private ContainerInterface $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $registry = [];
        $localeSettings = $this->container->get('oro_locale.settings');
        $localeCode = $localeSettings->getLocale();

        $repository = $manager->getRepository(Localization::class);

        foreach ($this->localizations as $item) {
            /** @var Language $language */
            $language = $this->getReference($item['language']);
            $name = Locales::getName($item['formatting'], $localeCode);

            $localization = $repository->findOneBy(['name' => $name]);

            if (!$localization) {
                $localization = new Localization();
                $localization
                    ->setLanguage($language)
                    ->setFormattingCode($item['formatting'])
                    ->setName($name)
                    ->setDefaultTitle($name);

                if ($item['parent']) {
                    $parentCode = $item['parent'];

                    if (isset($registry[$parentCode])) {
                        $localization->setParentLocalization($registry[$parentCode]);
                    }
                }

                $manager->persist($localization);
            }

            $registry[$language->getCode()] = $localization;

            $this->addReference('localization_' . $language->getCode(), $localization);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadLanguageDemoData::class];
    }
}
