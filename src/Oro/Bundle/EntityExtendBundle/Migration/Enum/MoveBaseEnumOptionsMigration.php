<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Psr\Container\ContainerInterface;

/**
 * Move legacy oro enums with data to enum options table.
 */
class MoveBaseEnumOptionsMigration implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * {'enum_class_name' => 'enum_table_name'}
     */
    private array $enumTableNamesMap = [];

    /**
     * {'language_code' => 'language_id'}
     */
    private array $languageIdMap = [];

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $entityConfigs = $this->connection->fetchAllAssociative(
            'SELECT id, class_name, data FROM oro_entity_config'
        );
        // prepare enum table names map
        $this->prepareEnumTableMap($entityConfigs);
        foreach ($entityConfigs as $entityConfig) {
            $entityConfigData = $this->connection->convertToPHPValue($entityConfig['data'], 'array');
            if (!isset($entityConfigData['extend']['is_extend'])
                || !$entityConfigData['extend']['is_extend']
                || $entityConfig['class_name'] === EnumOption::class
                || str_starts_with($entityConfig['class_name'], ExtendHelper::ENTITY_NAMESPACE)) {
                continue;
            }
            $fieldConfigs = $this->connection->fetchAllAssociative(
                'SELECT data FROM oro_entity_config_field WHERE entity_id = :entity_id AND type IN (:enum_types)',
                ['entity_id' => $entityConfig['id'], 'enum_types' => ['enum', 'multiEnum']],
                ['entity_id' => Types::STRING, 'enum_types' => Connection::PARAM_STR_ARRAY]
            );
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldConfigData = $this->connection->convertToPHPValue($fieldConfig['data'], 'array');
                if (!isset($fieldConfigData['enum']['enum_code'])
                    || !isset($fieldConfigData['extend']['target_entity'])) {
                    continue;
                }
                $this->createEnumOptions($fieldConfigData);
            }
        }
    }

    private function createEnumOptions(array $fieldConfigData): void
    {
        $enumCode = $fieldConfigData['enum']['enum_code'];
        $enumClassName = $fieldConfigData['extend']['target_entity'];
        $enumTableName = $this->getEnumTableName($enumClassName);
        $baseEnumOptions = $this->connection->fetchAllAssociative('SELECT * FROM ' . $enumTableName);
        if (empty($baseEnumOptions)) {
            return;
        }
        if ($this->isAlreadyExistOptions($enumCode)) {
            return;
        }
        foreach ($baseEnumOptions as $enumValue) {
            $newEnumOptionId = ExtendHelper::buildEnumOptionId($enumCode, $enumValue['id']);
            $this->connection->executeQuery(
                'INSERT INTO oro_enum_option'
                . ' VALUES (:id, :internal_id, :enum_code, :name, :priority, :is_default)',
                [
                    'id' => $newEnumOptionId,
                    'internal_id' => $enumValue['id'],
                    'enum_code' => $enumCode,
                    'name' => $enumValue['name'],
                    'priority' => $enumValue['priority'],
                    'is_default' => $enumValue['is_default'],
                ],
                [
                    'id' => Types::STRING,
                    'internal_id' => Types::STRING,
                    'enum_code' => Types::STRING,
                    'name' => Types::STRING,
                    'priority' => Types::INTEGER,
                    'is_default' => Types::BOOLEAN,
                ]
            );
            $this->moveEnumTranslations($newEnumOptionId, $enumValue['name']);
        }
        $this->moveOtherEnumTranslations($enumCode, $enumClassName);
    }

    private function moveEnumTranslations(
        string $enumOptionId,
        string $value,
        ?string $code = null
    ): void {
        if (null === $code) {
            $code = $this->container->getParameter('oro_locale.language');
        }
        $enumTranslationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOptionId);
        $existsTranslationKey = $this->getTranslationKey($enumTranslationKey);
        if (false === $existsTranslationKey) {
            $this->connection->executeQuery(
                'INSERT INTO oro_translation_key (key, domain) VALUES (:key, :domain)',
                ['key' => $enumTranslationKey, 'domain' => 'messages']
            );
        }
        $languageId = $this->getLanguageId($code);
        $transKey = false !== $existsTranslationKey
            ? $existsTranslationKey
            : $this->getTranslationKey($enumTranslationKey);
        $this->connection->executeQuery(
            'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) '
            . 'VALUES (:key_id, :language_id, :value, :scope)',
            [
                'key_id' => $transKey['id'],
                'language_id' => $languageId,
                'value' => $value,
                'scope' => Translation::SCOPE_UI
            ]
        );
    }

    private function moveGedmoEnumTranlations(
        array $gedmoTranslation,
        string $enumOptionId,
    ): void {
        $this->connection->executeQuery(
            'INSERT INTO oro_enum_option_trans (foreign_key, content, locale, object_class, field) '
            . 'VALUES (:foreign_key, :content, :locale, :object_class, :field)',
            [
                'foreign_key' => $enumOptionId,
                'content' => $gedmoTranslation['content'],
                'locale' => $gedmoTranslation['locale'],
                'object_class' => EnumOption::class,
                'field' => $gedmoTranslation['field']
            ]
        );
    }

    private function moveOtherEnumTranslations(string $enumCode, string $enumClassName): void
    {
        $enumTranslations = $this->connection->fetchAllAssociative(
            'SELECT * FROM oro_enum_value_trans WHERE object_class = :object_class',
            ['object_class' => $enumClassName]
        );
        foreach ($enumTranslations as $enumTranslation) {
            $serializedEnumOptionId = ExtendHelper::buildEnumOptionId(
                $enumCode,
                $enumTranslation['foreign_key']
            );
            $this->moveEnumTranslations(
                $serializedEnumOptionId,
                $enumTranslation['content'],
                $enumTranslation['locale']
            );
            $this->moveGedmoEnumTranlations($enumTranslation, $serializedEnumOptionId);
        }
    }

    private function getLanguageId(string $code): int
    {
        if (isset($this->languageIdMap[$code])) {
            return $this->languageIdMap[$code];
        }
        $language = $this->connection->fetchAssociative(
            'SELECT id from oro_language WHERE code = :code',
            ['code' => $code]
        );
        if (!isset($language['id'])) {
            throw new \LogicException(sprintf('Failed to load language by code: %s', $code));
        }
        $this->languageIdMap[$code] = $language['id'];

        return $this->languageIdMap[$code];
    }

    private function isAlreadyExistOptions(string $enumCode): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT id from oro_enum_option where enum_code = :enum_code',
            ['enum_code' => $enumCode]
        );
    }

    private function getTranslationKey(string $enumTranslationKey): mixed
    {
        return $this->connection->fetchAssociative(
            'SELECT id FROM oro_translation_key where key = :key',
            ['key' => $enumTranslationKey]
        );
    }

    private function prepareEnumTableMap(array $extendEntityConfigs): void
    {
        foreach ($extendEntityConfigs as $entityConfig) {
            $className = $entityConfig['class_name'];
            if (!str_starts_with($entityConfig['class_name'], ExtendHelper::ENTITY_NAMESPACE)) {
                continue;
            }
            $entityConfigData = $this->connection->convertToPHPValue($entityConfig['data'], 'array');
            if (!$entityConfigData['extend']['is_extend'] || !isset($entityConfigData['extend']['table'])) {
                continue;
            }
            $this->enumTableNamesMap[$className] = $entityConfigData['extend']['table'];
        }
    }

    private function getEnumTableName(string $enumClassName): string
    {
        if (!isset($this->enumTableNamesMap[$enumClassName])) {
            throw new \LogicException(sprintf('Undefined enum class name passed: %s', $enumClassName));
        }

        return $this->enumTableNamesMap[$enumClassName];
    }
}
