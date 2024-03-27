<?php

namespace Oro\Bundle\SanitizeBundle\Provider\Rule;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SanitizeBundle\Provider\EntityAllMetadataProvider;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainer;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * Provider of sanitizing rules and raw SQLs read from the file.
 */
class FileBasedProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/sanitize.yml';

    private ?array $rawSqls = null;
    private ?array $entitySanitizeMap = null;
    private ?array $fieldSanitizeMap = null;
    private ?array $entityTableNameMap = null;
    private ?array $fieldColumnNameMap = null;
    private ?array $unboundRuleMessages = null;

    public function __construct(
        private EntityAllMetadataProvider $metadataProvider,
        private ConfigManager $configManager,
        private FileBasedConfiguration $sanitizeConfiguration
    ) {
    }

    public function getRawSqls(): array
    {
        $this->ensureRulesAndSqlsLoaded();

        return $this->rawSqls;
    }

    public function getEntitySanitizeRule(ClassMetadata $metadata): string
    {
        $this->ensureRulesAndSqlsLoaded();

        $mapKeys = [$metadata->getName(), $metadata->getTableName()];
        foreach ($mapKeys as $mapKey) {
            if (!empty($this->entitySanitizeMap[$mapKey]['rule'])) {
                return $this->entitySanitizeMap[$mapKey]['rule'];
            }
        }

        return '';
    }

    public function getEntitySanitizeRuleOptions(ClassMetadata $metadata): array
    {
        $this->ensureRulesAndSqlsLoaded();

        $mapKeys = [$metadata->getName(), $metadata->getTableName()];
        foreach ($mapKeys as $mapKey) {
            if (!empty($this->entitySanitizeMap[$mapKey]['rule_options'])) {
                return $this->entitySanitizeMap[$mapKey]['rule_options'];
            }
        }

        return [];
    }

    public function getEntitySanitizeRawSqls(ClassMetadata $metadata): array
    {
        $this->ensureRulesAndSqlsLoaded();

        $mapKeys = [$metadata->getName(), $metadata->getTableName()];
        foreach ($mapKeys as $mapKey) {
            if (!empty($this->entitySanitizeMap[$mapKey]['raw_sqls'])) {
                return $this->entitySanitizeMap[$mapKey]['raw_sqls'];
            }
        }

        return [];
    }

    public function getFieldSanitizeRule(string $fieldName, ClassMetadata $metadata): string
    {
        $this->ensureRulesAndSqlsLoaded();

        foreach ($this->getFieldMapKey($fieldName, $metadata) as $mapKey) {
            if (!empty($this->fieldSanitizeMap[$mapKey]['rule'])) {
                return $this->fieldSanitizeMap[$mapKey]['rule'];
            }
        }

        return '';
    }

    public function getFieldSanitizeRuleOptions(string $fieldName, ClassMetadata $metadata): array
    {
        $this->ensureRulesAndSqlsLoaded();

        foreach ($this->getFieldMapKey($fieldName, $metadata) as $mapKey) {
            if (!empty($this->fieldSanitizeMap[$mapKey]['rule_options'])) {
                return $this->fieldSanitizeMap[$mapKey]['rule_options'];
            }
        }

        return [];
    }

    public function getFieldSanitizeRawSqls(string $fieldName, ClassMetadata $metadata): array
    {
        $this->ensureRulesAndSqlsLoaded();

        foreach ($this->getFieldMapKey($fieldName, $metadata) as $mapKey) {
            if (!empty($this->fieldSanitizeMap[$mapKey]['raw_sqls'])) {
                return $this->fieldSanitizeMap[$mapKey]['raw_sqls'];
            }
        }

        return [];
    }

    public function getUnboundRuleMessages(): array
    {
        $this->ensureRulesAndSqlsLoaded();

        return $this->unboundRuleMessages;
    }

    protected function comulativeSaniztizeConfigLoad(): array
    {
        $configLoader = CumulativeConfigLoaderFactory::create(FileBasedConfiguration::ROOT_NODE, self::CONFIG_FILE);

        return $configLoader->load(new ResourcesContainer());
    }

    private function getFieldMapKey(string $fieldName, ClassMetadata $metadata): array
    {
        $tableName = $metadata->getTableName();
        $columnName = $metadata->getColumnName($fieldName);

        return [$tableName . ':' . $columnName, $tableName . ':' . $fieldName];
    }

    private function ensureRulesAndSqlsLoaded(): void
    {
        if (null !== $this->rawSqls) {
            return;
        }
        $this->prepareEntityAndFieldToDdlNamingMap();

        $this->rawSqls
            = $this->entitySanitizeMap
            = $this->fieldSanitizeMap
            = $this->unboundRuleMessages
            = [];

        $resources = $this->comulativeSaniztizeConfigLoad();
        foreach ($resources as $resource) {
            try {
                if (!empty($resource->data[FileBasedConfiguration::ROOT_NODE])) {
                    $processor = new Processor();
                    if (!is_array($resource->data[FileBasedConfiguration::ROOT_NODE])) {
                        throw new \RuntimeException(sprintf(
                            '"%s" keyed data must be type of array',
                            FileBasedConfiguration::ROOT_NODE
                        ));
                    }

                    $config = $processor->processConfiguration(
                        $this->sanitizeConfiguration,
                        [$resource->data[FileBasedConfiguration::ROOT_NODE]]
                    );

                    $curruptedRules = $this->processSanitizeConfigs($config);
                    $this->unboundRuleMessages[] = $curruptedRules;
                }
            } catch (InvalidConfigurationException $e) {
                throw new InvalidConfigurationException(sprintf(
                    '%s' . PHP_EOL . 'Issued file: %s',
                    $e->getMessage(),
                    $resource->path,
                ));
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    '%s' . PHP_EOL . 'Issued file: %s',
                    $e->getMessage(),
                    $resource->path,
                ));
            }
        }

        $this->unboundRuleMessages = count($this->unboundRuleMessages)
            ? array_merge(...$this->unboundRuleMessages)
            : [];
        $this->rawSqls = array_unique(array_merge(...$this->rawSqls));
    }

    private function processSanitizeConfigs($config): array
    {
        $this->rawSqls[] = $config['raw_sqls'];
        if (empty($config['entity'])) {
            return [];
        }

        $unboundRuleMessages = [];

        foreach ($config['entity'] as $entityOrTableName => $entitySanitizeConfig) {
            $tableName = $this->entityTableNameMap[0][$entityOrTableName] ?? $entityOrTableName;
            if (!isset($this->entityTableNameMap[1][$tableName])) {
                $unboundRuleMessages[] = sprintf(
                    "Reference in the sanitizing rule to a non-existing entity class or table name '%s'",
                    $entityOrTableName
                );

                continue;
            }

            if (!empty($entitySanitizeConfig['rule']) || !empty($entitySanitizeConfig['raw_sqls'])) {
                // The data of each resource overrides the entity's sanitization configuration defined previously
                $this->entitySanitizeMap[$tableName] = [
                    'rule' => $entitySanitizeConfig['rule'],
                    'rule_options' => $entitySanitizeConfig['rule_options'],
                    'raw_sqls' => $entitySanitizeConfig['raw_sqls']
                ];
            }

            $unboundFieldOrColumnNames
                = $this->processFieldsSanitizeConfig($tableName, $entitySanitizeConfig['fields']);
            foreach ($unboundFieldOrColumnNames as $fieldOrColumnName) {
                $unboundRuleMessages[] = sprintf(
                    "Reference in the sanitizing rule to a non-existing field or column name '%s'"
                    . " of entity class or table name '%s'",
                    $fieldOrColumnName,
                    $entityOrTableName
                );
            }
        }

        return $unboundRuleMessages;
    }

    private function processFieldsSanitizeConfig(string $tableName, array $fieldsSanitizeConfig): array
    {
        $unboundFieldOrColumnNames = [];

        foreach ($fieldsSanitizeConfig as $fieldOrColumnName => $fieldSanitizeConfig) {
            $columnName = $this->fieldColumnNameMap[0][$tableName . ':' . $fieldOrColumnName] ?? $fieldOrColumnName;
            if (!isset($this->fieldColumnNameMap[1][$tableName . ':' . $columnName])) {
                $unboundFieldOrColumnNames[] = $fieldOrColumnName;

                continue;
            }

            // The data of each resource overrides the field's sanitization configuration defined previously
            $this->fieldSanitizeMap[$tableName . ':' . $columnName] = [
                'rule' => $fieldSanitizeConfig['rule'],
                'rule_options' => $fieldSanitizeConfig['rule_options'],
                'raw_sqls' => $fieldSanitizeConfig['raw_sqls']
            ];
        }

        return $unboundFieldOrColumnNames;
    }

    private function prepareEntityAndFieldToDdlNamingMap(): void
    {
        if (null !== $this->entityTableNameMap) {
            return;
        }

        $this->entityTableNameMap = $this->fieldColumnNameMap = [[], []];

        foreach ($this->metadataProvider->getAllMetadata() as $metadata) {
            $tableName = $metadata->getTableName();
            $className = $metadata->getName();

            $this->entityTableNameMap[0][$className] = $tableName;
            $this->entityTableNameMap[1][$tableName] = true;

            foreach ($metadata->getFieldNames() as $fieldName) {
                $column = $metadata->getColumnName($fieldName);
                $this->fieldColumnNameMap[0][$tableName . ':' . $fieldName] = $column;
                $this->fieldColumnNameMap[1][$tableName . ':' . $column] = true;
            }

            $fieldConfigs = $this->configManager->hasConfig($className)
                ? $this->configManager->getConfigs('extend', $className, true)
                : [];
            foreach ($fieldConfigs as $fieldConfig) {
                if ($fieldConfig->get('is_serialized')) {
                    $fieldName = $fieldConfig->getId()->getFieldName();
                    $this->fieldColumnNameMap[0][$tableName . ':' . $fieldName] = $fieldName;
                    $this->fieldColumnNameMap[1][$tableName . ':' . $fieldName] = true;
                }
            }
        }
    }
}
