<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The loader to load entity configs from annotations to a database.
 * It performs update routines only via DBAL.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigLoader
{
    private const FIELD_MAP_ID_ONLY = 0;
    private const FIELD_MAP_WITH_DATA = 1;

    private EntityManagerBag $entityManagerBag;
    private MetadataFactory $metadataFactory;
    private ConfigurationHandler $configurationHandler;
    private ConfigProviderBag $providerBag;
    private CacheItemPoolInterface $cache;
    private ?Connection $connection = null;

    private array $queries = [];
    private array $readClassEntityMap = [];
    private array $readEntityFieldMap = [];
    private array $readIndicesMap = [];
    private array $fieldNameToDataMap = [];

    public function __construct(
        EntityManagerBag $entityManagerBag,
        MetadataFactory $metadataFactory,
        ConfigurationHandler $configurationHandler,
        ConfigProviderBag $providerBag,
        CacheItemPoolInterface $cache
    ) {
        $this->entityManagerBag = $entityManagerBag;
        $this->metadataFactory = $metadataFactory;
        $this->configurationHandler = $configurationHandler;
        $this->providerBag = $providerBag;
        $this->cache = $cache;
    }

    /**
     * Loads entity configs from annotations to a database.
     */
    public function load(): void
    {
        $entityManagers = $this->entityManagerBag->getEntityManagers();
        foreach ($entityManagers as $em) {
            try {
                $this->connection = $em->getConnection();
                $this->connection->beginTransaction();

                $this->retrieveClassEntityMap();
                $this->retrieveEntityFieldMap();
                $this->retrieveIndicesMap();
                foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
                    $this->loadEntityConfigs($metadata);
                }
                $this->executeQueries();

                $this->retrieveEntityFieldMap(self::FIELD_MAP_ID_ONLY);
                $this->updateFieldConfigIndices();

                $this->connection->commit();
            } catch (\Exception $ex) {
                $this->connection->rollback();
                throw $ex;
            } finally {
                $this->cache->clear();
            }
        }
    }

    private function retrieveClassEntityMap(): void
    {
        $readClassEntityMap = [];

        $result = $this->connection
            ->executeQuery('SELECT class_name, id, data FROM oro_entity_config')
            ->fetchAllAssociative();
        foreach ($result as $item) {
            $readClassEntityMap[$item['class_name']] = [
                'id' => (int) $item['id'],
                'data' => $this->connection->convertToPHPValue($item['data'], Types::ARRAY) ?: []
            ];
        }

        $this->readClassEntityMap = $readClassEntityMap;
    }

    private function retrieveEntityFieldMap(int $type = self::FIELD_MAP_WITH_DATA): void
    {
        $readEntityFieldMap = [];

        switch ($type) {
            case self::FIELD_MAP_WITH_DATA:
                $result = $this->connection
                    ->executeQuery('SELECT id, entity_id, field_name, data FROM oro_entity_config_field')
                    ->fetchAllAssociative();
                foreach ($result as $item) {
                    $readEntityFieldMap[$item['entity_id'] . '|' . $item['field_name']] = [
                        'id' => (int) $item['id'],
                        'data' => $this->connection->convertToPHPValue($item['data'], Types::ARRAY) ?: []
                    ];
                }
                break;
            case self::FIELD_MAP_ID_ONLY:
                $result = $this->connection
                    ->executeQuery('SELECT id, entity_id, field_name FROM oro_entity_config_field')
                    ->fetchAllAssociative();
                foreach ($result as $item) {
                    $readEntityFieldMap[$item['entity_id'] . '|' . $item['field_name']] = (int) $item['id'];
                }
                break;
            default:
                break;
        }

        $this->readEntityFieldMap = $readEntityFieldMap;
    }

    private function retrieveIndicesMap(): void
    {
        $readIndicesMap = [];

        $result = $this->connection
            ->executeQuery(
                'SELECT id, field_id, entity_id, code, scope ' .
                'FROM oro_entity_config_index_value '
            )
            ->fetchAllAssociative();
        foreach ($result as $item) {
            $mapId = implode('|', [$item['field_id'] ?: '' , $item['entity_id'] ?: '', $item['code'], $item['scope']]);
            $readIndicesMap[$mapId] = [$item['id']];
        }

        $this->readIndicesMap = $readIndicesMap;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function loadEntityConfigs(ClassMetadata $metadata): void
    {
        if (ExtendHelper::isCustomEntity($metadata->getName())) {
            return;
        }

        $className = $metadata->getName();
        $classMetadata = $this->metadataFactory->getMetadataForClass($className);
        if (null === $classMetadata) {
            return;
        }

        $providers = $this->providerBag->getProviders();
        $this->configurationHandler->validateScopes($classMetadata, $providers, $className);

        $defaultValues = [];
        foreach (array_keys($providers) as $scope) {
            $defaultValuesForScope = $this->getEntityDefaultValues($scope, $className, $classMetadata);
            if (count($defaultValuesForScope)) {
                $defaultValues[$scope] = $defaultValuesForScope;
            }
        }

        if (!isset($this->readClassEntityMap[$className])) {
            if (ExtendHelper::isExtendEntity($className)) {
                $defaultValues['extend']['is_extend'] = true;
            }
            $defaultValues['extend']['pk_columns'] = $metadata->getIdentifierColumnNames();
        }

        $data = $defaultValues;

        $fieldNames = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());
        $entityConfigId = array_key_exists($className, $this->readClassEntityMap)
            ? (int) $this->readClassEntityMap[$className]['id']
            : null;
        foreach ($fieldNames as $fieldName) {
            if (!array_key_exists($entityConfigId . '|' . $fieldName, $this->readEntityFieldMap)) {
                $data['extend']['upgradeable'] = true;
                break;
            }
        }

        $hasChanges = true;
        if (!array_key_exists($className, $this->readClassEntityMap)) {
            $mode = $classMetadata->mode ?: 'default';
            $sql = 'INSERT INTO oro_entity_config '
                   . '(class_name, created, updated, mode, data) '
                   . 'VALUES(?, ?, ?, ?, ?)';
            $params = [
                $className,
                new \DateTime(),
                new \DateTime(),
                $mode,
                $data
            ];
            $types = [
                Types::STRING,
                Types::DATE_MUTABLE,
                Types::DATE_MUTABLE,
                Types::STRING,
                Types::ARRAY
            ];
        } else {
            $data = $this->readClassEntityMap[$className]['data'];
            $hasChanges = $this->updateConfigValues($data, $defaultValues);
            if (ExtendHelper::isExtendEntity($className) && empty($defaultValues['extend']['is_extend'])) {
                $data['extend']['is_extend'] = $hasChanges = true;
            }
            if ($hasChanges) {
                $sql = 'UPDATE oro_entity_config '
                    . 'SET updated=?, data=? '
                    . 'WHERE class_name=?';
                $params = [
                    new \DateTime(),
                    $data,
                    $className
                ];
                $types = [
                    Types::DATE_MUTABLE,
                    Types::ARRAY,
                    Types::STRING
                ];
            }
        }

        if ($hasChanges) {
            $this->connection->executeStatement($sql, $params, $types);
            $entityConfigId = $entityConfigId ?: (int) $this->connection->lastInsertId();
            $this->prepareEntityIndexQueries($entityConfigId, $className, $data);
        }

        foreach ($metadata->getFieldNames() as $fieldName) {
            $fieldType = $metadata->getTypeOfField($fieldName);
            $this->loadFieldConfigs($entityConfigId, $className, $fieldName, $fieldType, $classMetadata);
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            $associationType = $metadata->isSingleValuedAssociation($associationName) ? 'ref-one' : 'ref-many';
            $this->loadFieldConfigs($entityConfigId, $className, $associationName, $associationType, $classMetadata);
        }
    }

    private function loadFieldConfigs(
        int $entityConfigId,
        string $className,
        string $fieldName,
        string $fieldType,
        EntityMetadata $classMetadata
    ): void {
        if (!isset($classMetadata->fieldMetadata[$fieldName])) {
            return;
        }

        $fieldConfigInfo = $this->readEntityFieldMap[$entityConfigId . '|' . $fieldName] ?? null;
        $fieldMetadata = $classMetadata->fieldMetadata[$fieldName];
        $providers = $this->providerBag->getProviders();
        $this->configurationHandler->validateScopes($fieldMetadata, $providers, $className);

        $defaultValues = [];
        foreach (array_keys($providers) as $scope) {
            $defaultValuesForScope = $this->getFieldDefaultValues(
                $scope,
                $className,
                $fieldName,
                $fieldType,
                $fieldMetadata
            );
            if (count($defaultValuesForScope)) {
                $defaultValues[$scope] = $defaultValuesForScope;
            }
        }

        $data = $defaultValues;
        $hasChanges = true;
        if (null === $fieldConfigInfo) {
            $mode = $fieldMetadata->mode ?: 'default';
            $this->queries[] = [
                'INSERT INTO oro_entity_config_field '
                    . '(entity_id, field_name, type, created, updated, mode, data) '
                    . 'VALUES(?, ?, ?, ?, ?, ?, ?)'
                ,
                [
                    $entityConfigId,
                    $fieldName,
                    $fieldType,
                    new \DateTime(),
                    new \DateTime(),
                    $mode,
                    $defaultValues
                ],
                [
                    Types::INTEGER,
                    Types::STRING,
                    Types::STRING,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                    Types::STRING,
                    Types::ARRAY
                ]
            ];
        } else {
            $data = $fieldConfigInfo['data'];
            $hasChanges = $this->updateConfigValues($data, $defaultValues);
            if ($hasChanges) {
                $fieldConfigId = $fieldConfigInfo['id'];
                $this->queries[] = [
                    'UPDATE oro_entity_config_field '
                        . 'SET updated=?, data=?'
                        . 'WHERE id=?',
                    [
                        new \DateTime(),
                        $data,
                        $fieldConfigId
                    ],
                    [
                        Types::DATE_MUTABLE,
                        Types::ARRAY,
                        Types::INTEGER
                    ]
                ];
            }
        }

        if ($hasChanges) {
            $this->fieldNameToDataMap[implode('|', [$entityConfigId, $fieldName])] = $data;
        }
    }

    private function getPropertyConfig($scope)
    {
        return $this->providerBag->getProvider($scope)->getPropertyConfig();
    }

    /**
     * Extracts entity default values from an annotation and config file
     */
    private function getEntityDefaultValues(
        string $scope,
        string $className = null,
        EntityMetadata $metadata = null
    ): array {
        $propertyConfig = $this->getPropertyConfig($scope);
        $defaultValues = $this->configurationHandler->process(
            ConfigurationHandler::CONFIG_ENTITY_TYPE,
            $scope,
            $metadata->defaultValues[$scope] ?? [],
            $className
        );

        // process translatable values
        if ($className) {
            $translatablePropertyNames = $propertyConfig->getTranslatableValues(PropertyConfigContainer::TYPE_ENTITY);
            foreach ($translatablePropertyNames as $propertyName) {
                if (empty($defaultValues[$propertyName])) {
                    $defaultValues[$propertyName] =
                        ConfigHelper::getTranslationKey($scope, $propertyName, $className);
                }
            }
        }

        return $defaultValues;
    }

    /**
     * Extracts field default values from an annotation and config file
     */
    private function getFieldDefaultValues(
        string $scope,
        string $className,
        string $fieldName,
        string $fieldType,
        FieldMetadata $metadata = null
    ): array {
        $propertyConfig = $this->getPropertyConfig($scope);
        $defaultValues = $this->configurationHandler->process(
            ConfigurationHandler::CONFIG_FIELD_TYPE,
            $scope,
            $metadata->defaultValues[$scope] ?? [],
            $className,
            $fieldType
        );
        // process translatable values
        $translatablePropertyNames = $propertyConfig->getTranslatableValues(PropertyConfigContainer::TYPE_FIELD);
        foreach ($translatablePropertyNames as $propertyName) {
            if (empty($defaultValues[$propertyName])) {
                $defaultValues[$propertyName] =
                    ConfigHelper::getTranslationKey($scope, $propertyName, $className, $fieldName);
            }
        }

        return $defaultValues;
    }

    private function prepareEntityIndexQueries(int $entityId, string $className, array $data): void
    {
        list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($className);
        $data['entity_config'] = ['entity_name' => $entityName, 'module_name' => $moduleName];

        $this->prepareConfigIndexQueryByType($entityId, 'entity_id', $data);
    }

    private function updateFieldConfigIndices(): void
    {
        foreach ($this->fieldNameToDataMap as $mapKey => $data) {
            $this->prepareConfigIndexQueryByType($this->readEntityFieldMap[$mapKey], 'field_id', $data);
        }

        $this->executeQueries();
    }

    private function prepareConfigIndexQueryByType(int $id, string $idField, array $data): void
    {
        $type = $idField === 'entity_id'
            ? PropertyConfigContainer::TYPE_ENTITY
            : PropertyConfigContainer::TYPE_FIELD
        ;

        foreach ($data as $scope => $values) {
            $indexed = $this->getPropertyConfig($scope)->getIndexedValues($type);

            foreach ($values as $code => $value) {
                if (!isset($indexed[$code])) {
                    continue;
                }

                $mapId = implode('|', $idField === 'entity_id' ? ['', $id, $code, $scope] : [$id, '', $code, $scope]);
                if (!isset($this->readIndicesMap[$mapId])) {
                    if (is_bool($value)) {
                        $value = (int)$value;
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $value = (string) $value;

                    $this->queries[] = [
                        'INSERT INTO oro_entity_config_index_value (' . $idField . ', scope, code, value) '
                            . 'VALUES(?, ?, ?, ?)',
                        [$id, $scope, $code, $value],
                        [Types::INTEGER, Types::STRING, Types::STRING, Types::STRING]
                    ];
                }
            }
        }
    }

    private function updateConfigValues(array &$data, array $defaultValues): bool
    {
        $hasChanges = false;
        foreach ($defaultValues as $scope => $values) {
            if (!isset($data[$scope])) {
                $data[$scope] = $values;
                $hasChanges = true;
                continue;
            }

            $dataScope = &$data[$scope];
            foreach ($values as $code => $value) {
                if (!isset($dataScope[$code])) {
                    $dataScope[$code] = $value;
                    $hasChanges = true;
                }
            }
        }

        return $hasChanges;
    }

    private function executeQueries(): void
    {
        $sql = '';
        $params = $types = [];
        $batchCount = $queryNum = 1;
        $queriesCount = count($this->queries);

        foreach ($this->queries as $query) {
            $sql .= $query[0] . ';';
            $params = array_merge($params, $query[1]);
            $types = array_merge($types, $query[2]);

            if ($batchCount === 300 || $queryNum === $queriesCount) {
                $this->connection->executeStatement($sql, $params, $types);
                $sql = '';
                $params = $types = [];
                $batchCount = 0;
            }

            $batchCount++;
            $queryNum++;
        }

        $this->queries = [];
    }
}
