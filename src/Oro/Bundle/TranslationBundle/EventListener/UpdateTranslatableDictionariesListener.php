<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Updates Gedmo translatable dictionaries when translations are changed in the database.
 */
class UpdateTranslatableDictionariesListener
{
    private const CHANGED = 0;
    private const REMOVED = 1;

    private const TRANSLATION_DOMAIN = 'entities';

    /** @var array [entity class => [translatable field name => [translation key prefix, key field name], ...], ...] */
    private array $entities = [];
    /**
     * @var array
     * [type => [entity class => [translatable field name => [locale => [key => value, ...], ...], ...], ...], ...]
     */
    private array $data = [];

    /**
     * Registers mapping between a translation key prefix and a translatable entity field.
     */
    public function addEntity(
        string $entityClass,
        string $translatableFieldName,
        string $translationKeyPrefix,
        string $keyFieldName
    ): void {
        $this->entities[$entityClass][$translatableFieldName] = [$translationKeyPrefix, $keyFieldName];
    }

    public function postPersist(Translation $translation): void
    {
        $this->postUpdate($translation);
    }

    public function postUpdate(Translation $translation): void
    {
        $translationKeyEntity = $translation->getTranslationKey();
        if (self::TRANSLATION_DOMAIN !== $translationKeyEntity->getDomain()) {
            return;
        }

        foreach ($this->entities as $entityClass => $fields) {
            foreach ($fields as $translatableFieldName => [$translationKeyPrefix]) {
                $translationKey = $translationKeyEntity->getKey();
                if (self::isApplicable($translationKey, $translationKeyPrefix)) {
                    $locale = $translation->getLanguage()->getCode();
                    $key = self::getTranslationCode($translationKey, $translationKeyPrefix);
                    $this->data[self::CHANGED][$entityClass][$translatableFieldName][$locale][$key] =
                        $translation->getValue();
                }
            }
        }
    }

    public function postRemove(Translation $translation): void
    {
        $translationKeyEntity = $translation->getTranslationKey();
        if (self::TRANSLATION_DOMAIN !== $translationKeyEntity->getDomain()) {
            return;
        }

        foreach ($this->entities as $entityClass => $fields) {
            foreach ($fields as $translatableFieldName => [$translationKeyPrefix]) {
                $translationKey = $translationKeyEntity->getKey();
                if (self::isApplicable($translationKey, $translationKeyPrefix)) {
                    $locale = $translation->getLanguage()->getCode();
                    if (Translator::DEFAULT_LOCALE !== $locale) {
                        $key = self::getTranslationCode($translationKey, $translationKeyPrefix);
                        $this->data[self::REMOVED][$entityClass][$translatableFieldName][$locale][$key] = null;
                    }
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->data) {
            return;
        }

        $em = $event->getEntityManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            if (isset($this->data[self::CHANGED])) {
                $this->updateChangedTranslations($em, $this->data[self::CHANGED]);
            }
            if (isset($this->data[self::REMOVED])) {
                $this->updateRemovedTranslations($em, $this->data[self::REMOVED]);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $this->data = [];
        }
    }

    public function onClear(): void
    {
        if ($this->data) {
            $this->data = [];
        }
    }

    private function updateChangedTranslations(EntityManagerInterface $em, array $changedTranslations): void
    {
        foreach ($changedTranslations as $entityClass => $fields) {
            foreach ($fields as $translatableFieldName => $locales) {
                foreach ($locales as $locale => $data) {
                    if (Translator::DEFAULT_LOCALE === $locale) {
                        $this->updateTranslationsForDefaultLocale($em, $entityClass, $translatableFieldName, $data);
                    } else {
                        $this->updateTranslations($em, $entityClass, $translatableFieldName, $data, $locale);
                    }
                }
            }
        }
    }

    private function updateRemovedTranslations(EntityManagerInterface $em, array $removedTranslations): void
    {
        foreach ($removedTranslations as $entityClass => $fields) {
            foreach ($fields as $translatableFieldName => $locales) {
                foreach ($locales as $locale => $data) {
                    $this->removeTranslations($em, $entityClass, $translatableFieldName, array_keys($data), $locale);
                }
            }
        }
    }

    private function updateTranslationsForDefaultLocale(
        EntityManagerInterface $em,
        string $entityClass,
        string $translatableFieldName,
        array $data
    ): void {
        $metadata = $em->getClassMetadata($entityClass);
        $tableName = $metadata->getTableName();
        $translatableColumnName = $metadata->getColumnName($translatableFieldName);
        $keyColumnName = $metadata->getColumnName($this->entities[$entityClass][$translatableFieldName][1]);
        $connection = $em->getConnection();
        foreach ($data as $key => $value) {
            $connection->update(
                $tableName,
                [$translatableColumnName => $value],
                [$keyColumnName => $key]
            );
        }
    }

    private function updateTranslations(
        EntityManagerInterface $em,
        string $entityClass,
        string $translatableFieldName,
        array $data,
        string $locale
    ): void {
        $newData = [];
        $tableName = self::getTranslationEntityTable($em, $entityClass);
        $connection = $em->getConnection();
        foreach ($data as $key => $value) {
            $affectedRows = $connection->update(
                $tableName,
                ['content' => $value],
                [
                    'foreign_key'  => $key,
                    'locale'       => $locale,
                    'object_class' => $entityClass,
                    'field'        => $translatableFieldName
                ],
                array_fill(0, 5, \PDO::PARAM_STR)
            );
            if (0 === $affectedRows) {
                $newData[$key] = $value;
            }
        }
        if ($newData) {
            $placeholders = [];
            $params = [];
            foreach ($newData as $key => $value) {
                $placeholders[] = '(?, ?, ?, ?, ?)';
                $params[] = $key;
                $params[] = $locale;
                $params[] = $entityClass;
                $params[] = $translatableFieldName;
                $params[] = $value;
            }
            $connection->executeQuery(
                sprintf(
                    'INSERT INTO %s (foreign_key, locale, object_class, field, content) VALUES %s',
                    $tableName,
                    self::getSqlParamPlaceholders($placeholders)
                ),
                $params,
                array_fill(0, count($params), \PDO::PARAM_STR)
            );
        }
    }

    private function removeTranslations(
        EntityManagerInterface $em,
        string $entityClass,
        string $translatableFieldName,
        array $keys,
        string $locale
    ): void {
        $params = $keys;
        $params[] = $locale;
        $params[] = $entityClass;
        $params[] = $translatableFieldName;
        $em->getConnection()->executeQuery(
            sprintf(
                'DELETE FROM %s WHERE foreign_key IN (%s) AND locale = ? AND object_class = ? AND field = ?',
                self::getTranslationEntityTable($em, $entityClass),
                self::getSqlParamPlaceholders(array_fill(0, count($keys), '?'))
            ),
            $params,
            array_fill(0, count($params), \PDO::PARAM_STR)
        );
    }

    private static function isApplicable(string $translationKey, string $translationKeyPrefix): bool
    {
        return str_starts_with($translationKey, $translationKeyPrefix);
    }

    private static function getTranslationCode(string $translationKey, string $translationKeyPrefix): string
    {
        return substr($translationKey, \strlen($translationKeyPrefix));
    }

    private static function getTranslationEntityTable(EntityManagerInterface $em, string $entityClass): string
    {
        return $em->getClassMetadata($entityClass . 'Translation')->getTableName();
    }

    private static function getSqlParamPlaceholders(array $items): string
    {
        return implode(', ', $items);
    }
}
