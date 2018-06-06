<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigFieldValueQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fieldName;

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
     * @var null|string
     */
    protected $replaceValue;

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $scope
     * @param string $code
     * @param string|array $value
     * @param string|array $replaceValue if passed, updating will not happen if existing value !== replaceValue
     */
    public function __construct($entityName, $fieldName, $scope, $code, $value, $replaceValue = null)
    {
        $this->entityName   = $entityName;
        $this->fieldName    = $fieldName;
        $this->scope        = $scope;
        $this->code         = $code;
        $this->value        = $value;
        $this->replaceValue = $replaceValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            sprintf(
                'Set entity "%s" config value "%s" for field "%s" in scope "%s" to "%s"',
                $this->entityName,
                $this->code,
                $this->fieldName,
                $this->scope,
                var_export($this->value, true)
            )
        );
        $this->updateFieldConfig($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateFieldConfig($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateFieldConfig(LoggerInterface $logger, $dryRun = false)
    {
        $sql        = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';
        $parameters = [$this->entityName, $this->fieldName];
        $row        = $this->connection->fetchAssoc($sql, $parameters);

        if ($row) {
            $data = $row['data'];
            $id   = $row['id'];
            $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];

            if ($this->isDoUpdate($data)) {
                $data[$this->scope][$this->code] = $this->value;
                $data                            = $this->connection->convertToDatabaseValue($data, Type::TARRAY);
                // update field itself
                $sql        = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
                $parameters = [$data, $id];
                $this->logQuery($logger, $sql, $parameters);

                if (!$dryRun) {
                    $statement = $this->connection->prepare($sql);
                    $statement->execute($parameters);
                }
            }
        }
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
