<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Set specific row value in oro_entity_config_index_value table.
 */
class UpdateEntityConfigEntityValueQuery implements
    MigrationQuery,
    ConnectionAwareInterface,
    ConfigurationHandlerAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var null|string
     */
    protected $replaceValue;

    protected ConfigurationHandler $configurationHandler;

    /**
     * @param string $entityName
     * @param string $scope
     * @param string $code
     * @param string|array $value
     * @param string|array $replaceValue if passed, updating will not happen if existing value !== replaceValue
     */
    public function __construct($entityName, $scope, $code, $value, $replaceValue = null)
    {
        $this->entityName = $entityName;
        $this->scope = $scope;
        $this->code = $code;
        $this->value = $value;
        $this->replaceValue = $replaceValue;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setConfigurationHandler(ConfigurationHandler $configurationHandler): void
    {
        $this->configurationHandler = $configurationHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Set specific row value in oro_entity_config_index_value table';
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

    protected function updateEntityConfigIndexValue(LoggerInterface $logger)
    {
        $sql =
            "UPDATE oro_entity_config_index_value
            SET value = ?
            WHERE
                entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1) AND
                field_id IS NULL AND
                scope = ? AND
                code = ?
            ";

        $value = $this->value;

        // array values are not supported
        if (is_array($value)) {
            return;
        }

        $parameters = [$value, $this->entityName, $this->scope, $this->code];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);

        $logger->debug($sql);
    }

    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityName];
        $data = $this->connection->fetchColumn($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

        if ($this->isDoUpdate($data)) {
            if (!isset($data[$this->scope])) {
                $data[$this->scope] = [];
            }

            $data[$this->scope][$this->code] = $this->value;

            $data[$this->scope] = $this->configurationHandler->process(
                ConfigurationHandler::CONFIG_ENTITY_TYPE,
                $this->scope,
                $data[$this->scope],
                $this->entityName
            );

            $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

            $sql = 'UPDATE oro_entity_config SET data = ? WHERE class_name = ?';
            $parameters = [$data, $this->entityName];
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            $this->logQuery($logger, $sql, $parameters);
        }
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

    /**
     * @param array $data
     * @return bool
     */
    protected function isDoUpdate(array $data)
    {
        return !isset($data[$this->scope][$this->code])
            || $this->replaceValue === null
            || $this->replaceValue === $data[$this->scope][$this->code];
    }
}
