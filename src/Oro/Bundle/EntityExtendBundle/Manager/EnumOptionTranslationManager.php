<?php

namespace Oro\Bundle\EntityExtendBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Provides functionality to manage EnumOptionTranslation data based on Translation changes.
 */
class EnumOptionTranslationManager
{
    public const string DEFAULT_FIELD = 'name';

    private array $updatedEnumTranslations = [];
    private array $removedEnumTranslations = [];

    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function actualizeEnumOptionTranslation(
        string $id,
        string $locale,
        string $content,
        string $field = self::DEFAULT_FIELD
    ): void {
        if (Translator::DEFAULT_LOCALE !== $locale) {
            $enumTrans = $this->doctrine
                ->getManager()
                ->getRepository(EnumOptionTranslation::class)
                ->findOneBy(['foreignKey' => $id, 'locale' => $locale]);
            if (!$enumTrans) {
                $enumTrans = new EnumOptionTranslation();
                $enumTrans->setForeignKey($id);
                $enumTrans->setLocale($locale);
                $enumTrans->setField($field);
                $enumTrans->setObjectClass(EnumOption::class);
                $enumTrans->setContent($content);

                $this->doctrine->getManager()->persist($enumTrans);
            } else {
                $enumTrans->setContent($content);
            }

            $this->updatedEnumTranslations[$enumTrans->getForeignKey()] = $enumTrans;
        }
    }

    public function getTranslationData(Translation $entity): array
    {
        return [
            ExtendHelper::getEnumOptionIdFromTranslationKey($entity->getTranslationKey()->getKey()),
            $entity->getValue(),
            $entity->getLanguage()->getCode(),
        ];
    }

    public function flushEnumTranslations(): void
    {
        $entities = array_merge(
            $this->removedEnumTranslations,
            $this->updatedEnumTranslations
        );

        if ($entities) {
            $this->doctrine->getManager()->flush();
        }
    }

    public function removeEnumOptionTranslation(string $id, string $locale): void
    {
        if (Translator::DEFAULT_LOCALE !== $locale) {
            $enumTrans = $this->doctrine->getManager()
                ->getRepository(EnumOptionTranslation::class)
                ->findOneBy(['foreignKey' => $id, 'locale' => $locale]);

            if ($enumTrans) {
                $this->doctrine->getManager()->remove($enumTrans);
                $this->removedEnumTranslations[$enumTrans->getForeignKey()] = $enumTrans;
            }
        }
    }

    public function actualizeAllForLocale(string $locale): void
    {
        if (Translator::DEFAULT_LOCALE !== $locale) {
            $translations = $this->doctrine->getManager()
                ->getRepository(Translation::class)
                ->findValues(ExtendHelper::ENUM_TRANSLATION_PREFIX, $locale, TranslationManager::DEFAULT_DOMAIN);
            foreach ($translations as $translationKey => $translation) {
                $this->actualizeEnumOptionTranslation(
                    ExtendHelper::getEnumOptionIdFromTranslationKey($translationKey),
                    $locale,
                    $translation
                );
            }

            $this->flushEnumTranslations();
        }
    }
}
