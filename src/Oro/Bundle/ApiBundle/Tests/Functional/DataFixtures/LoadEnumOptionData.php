<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

class LoadEnumOptionData extends AbstractFixture implements DependentFixtureInterface
{
    public const string ENUM_CODE = 'test_enum_code';
    public const string OPTION_ID = self::ENUM_CODE . '.2_of_5';
    public const string LANGUAGE_CODE = 'fr_FR';
    public const string FOREIGN_TRANSLATION_KEY = 'api.enum_option.unrelated_label';
    public const string SYSTEM_OPTION_ID = 'dashboard_type.api_system_option';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadLanguages::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $manager->persist(new EnumOption(self::ENUM_CODE, '2 of 5', '2_of_5'));
        $manager->persist(new EnumOption(self::ENUM_CODE, 'Widgets', 'widgets'));
        $manager->persist(new EnumOption('dashboard_type', 'System option', 'api_system_option'));
        $manager->flush();

        $key = ExtendHelper::buildEnumOptionTranslationKey(self::OPTION_ID);
        $translationKey = $manager->getRepository(TranslationKey::class)->findOneBy([
            'key' => $key,
            'domain' => TranslationManager::DEFAULT_DOMAIN
        ]);
        if (null === $translationKey) {
            $translationKey = new TranslationKey();
            $translationKey->setKey($key);
            $translationKey->setDomain(TranslationManager::DEFAULT_DOMAIN);
            $manager->persist($translationKey);
        }
        $this->addReference('enumOptionTranslationKey', $translationKey);

        /** @var Language $language */
        $language = $this->getReference(self::LANGUAGE_CODE);
        $translation = new Translation();
        $translation->setLanguage($language);
        $translation->setTranslationKey($translationKey);
        $translation->setScope(Translation::SCOPE_UI);
        $translation->setValue('2 sur 5');
        $manager->persist($translation);

        $foreignKey = new TranslationKey();
        $foreignKey->setKey(self::FOREIGN_TRANSLATION_KEY);
        $foreignKey->setDomain(TranslationManager::DEFAULT_DOMAIN);
        $manager->persist($foreignKey);
        $foreignTranslation = new Translation();
        $foreignTranslation->setLanguage($language);
        $foreignTranslation->setTranslationKey($foreignKey);
        $foreignTranslation->setScope(Translation::SCOPE_UI);
        $foreignTranslation->setValue('Unrelated value');
        $manager->persist($foreignTranslation);
        $manager->flush();
    }
}
