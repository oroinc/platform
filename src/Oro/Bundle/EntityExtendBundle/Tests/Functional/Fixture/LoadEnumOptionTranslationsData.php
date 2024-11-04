<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * Loads translation Fixture for single enum option for secondary language
 */
class LoadEnumOptionTranslationsData extends AbstractFixture implements DependentFixtureInterface
{
    public const string ENUM_OPTION_ID = 'test_multi_enum.bob_marley';

    public const string LANGUAGE_CODE = 'fr_FR';

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $language = $manager->getRepository(Language::class)->findOneBy(['code' => self::LANGUAGE_CODE]);
        $translationKey = $manager
            ->getRepository(TranslationKey::class)
            ->findOneBy(['key' => ExtendHelper::buildEnumOptionTranslationKey(self::ENUM_OPTION_ID)]);
        if (null === $translationKey) {
            $translationKey = $this->processTranslationKey($manager);
        }
        $translation = new Translation();
        $translation->setLanguage($language);
        $translation->setTranslationKey($translationKey);
        $translation->setScope(Translation::SCOPE_UI);
        $translation->setValue('Bob Marley FR');

        $manager->persist($translation);
        $manager->flush();
    }

    protected function processTranslationKey(ObjectManager $manager): TranslationKey
    {
        $translationKey = new TranslationKey();
        $translationKey->setKey(ExtendHelper::buildEnumOptionTranslationKey(self::ENUM_OPTION_ID));
        $translationKey->setDomain(TranslationManager::DEFAULT_DOMAIN);
        $manager->persist($translationKey);
        $manager->flush([$translationKey]);

        return $translationKey;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadLanguages::class
        ];
    }
}
