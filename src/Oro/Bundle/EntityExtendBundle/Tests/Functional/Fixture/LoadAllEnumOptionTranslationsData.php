<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * Loads all translations Fixtures for enum options for secondary languages
 */
class LoadAllEnumOptionTranslationsData extends AbstractFixture implements DependentFixtureInterface
{
    private const array ENUM_OPTION_TRANSLATION_LANGUAGES = ['fr_FR'];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $translations = $manager->getRepository(Translation::class)->findBy([]);
        foreach ($translations as $translation) {
            foreach (self::ENUM_OPTION_TRANSLATION_LANGUAGES as $language) {
                $language = $manager->getRepository(Language::class)->findOneBy(['code' => $language]);
                $languageTranslation = new Translation();
                $languageTranslation->setLanguage($language);
                $languageTranslation->setTranslationKey($translation->getTranslationKey());
                $languageTranslation->setScope($translation->getScope());
                $languageTranslation->setValue(sprintf('%s %s', $translation->getValue(), $language->getCode()));

                $manager->persist($languageTranslation);
            }

            $manager->flush();
        }
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadLanguages::class
        ];
    }
}
