<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class MassUpdateEntityConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var array
     */
    protected $valuesForRemove;

    /**
     * @var array
     */
    protected $valuesForUpdate;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var null|array
     */
    protected $entityConfigRecordData = null;

    /**
     * @param string $entityName
     * @param array  $valuesForRemove Format: ['{scope}' => [{code}, {code}, ...], ...]
     * @param array  $valuesForUpdate Format: ['{scope}' => [{code} => {configuration value}, ... ], ...]
     */
    public function __construct($entityName, array $valuesForRemove, array $valuesForUpdate)
    {
        $this->entityName = $entityName;
        $this->valuesForRemove = $valuesForRemove;
        $this->valuesForUpdate = $valuesForUpdate;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update multiple entity configuration values and entity configuration index values';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        // update field itself
        $this->updateEntityConfigIndexValue($logger);

        // update entity config cached data
        $this->updateEntityConfig($logger);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function updateEntityConfigIndexValue(LoggerInterface $logger)
    {
        $entityConfigRecordData = $this->getEntityConfigRecordData();
        $entityConfigId = $entityConfigRecordData['id'];
        foreach ($this->valuesForRemove as $scope => $codes) {
            $codes = implode("', '", $codes);
            $sql = "DELETE FROM oro_entity_config_index_value
                    WHERE
                        entity_id = ? AND
                        field_id IS NULL AND
                        scope = ? AND
                        code IN ('$codes')
            ";

            $parameters = [$entityConfigId, $scope];
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            $this->logQuery($logger, $sql, $parameters);

            $logger->debug($sql);
        }

        foreach ($this->valuesForUpdate as $scope => $values) {
            foreach ($values as $code => $value) {
                $sql = "UPDATE oro_entity_config_index_value
                        SET value = ?
                        WHERE
                            entity_id = ? AND
                            field_id IS NULL AND
                            scope = ? AND
                            code = ?
                    ";
                $value = $this->convertEntityConfigIndexValueToDatabaseValue($value);
                $parameters = [$value, $entityConfigId, $scope, $code];
                $statement = $this->connection->prepare($sql);
                $statement->execute($parameters);
                $this->logQuery($logger, $sql, $parameters);

                $logger->debug($sql);
            }
        }
    }

    /**
     * @param bool|string|array $originalValue
     *
     * @return string
     */
    protected function convertEntityConfigIndexValueToDatabaseValue($originalValue)
    {
        $convertedValue = $originalValue;
        if (is_bool($originalValue)) {
            $convertedValue = (int)$originalValue;
        } elseif (is_array($originalValue)) {
            $convertedValue = json_encode($originalValue);
        }

        return (string)$convertedValue;
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $entityConfigRecordData = $this->getEntityConfigRecordData();

        $entityConfigData = empty($entityConfigRecordData['data'])
            ? []
            : $this->connection->convertToPHPValue($entityConfigRecordData['data'], Type::TARRAY);

        foreach ($this->valuesForRemove as $scope => $codes) {
            foreach ($codes as $code) {
                if (isset($entityConfigData[$scope][$code])) {
                    unset($entityConfigData[$scope][$code]);
                }
            }
        }
        foreach ($this->valuesForUpdate as $scope => $newScopeConfigurationValues) {
            $currentScopeConfigurationValues = isset($entityConfigData[$scope]) ? $entityConfigData[$scope] : [];
            $entityConfigData[$scope] = array_replace($currentScopeConfigurationValues, $newScopeConfigurationValues);
        }

        $entityConfigData = $this->connection->convertToDatabaseValue($entityConfigData, Type::TARRAY);
        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$entityConfigData, $entityConfigRecordData['id']];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * @return array
     */
    protected function getEntityConfigRecordData()
    {
        if (is_null($this->entityConfigRecordData)) {
            $this->entityConfigRecordData = $this
                ->connection
                ->fetchAssoc(
                    'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
                    [$this->entityName]
                );
        }

        return $this->entityConfigRecordData;
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @param array $parameters
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $parameters)
    {
        $message = sprintf('%s with parameters [%s]', $sql, implode(', ', $parameters));
        $logger->debug($message);
    }
}
