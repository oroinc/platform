<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    protected $entityFrom;

    /** @var string */
    protected $entityTo;

    /** @var string */
    protected $relationType;

    /** @var string */
    protected $field;

    /** @var mixed */
    private $value;

    /** @var mixed */
    private $key;

    /**
     * @param string $entityFrom   FQCN
     * @param string $entityTo     FQCN
     * @param string $relationType one of \Oro\Bundle\EntityExtendBundle\Extend\RelationType constants
     * @param string $field
     * @param string $key
     * @param mixed  $value
     */
    public function __construct($entityFrom, $entityTo, $relationType, $field, $key, $value)
    {
        $this->entityFrom = $entityFrom;
        $this->entityTo = $entityTo;
        $this->relationType = $relationType;
        $this->field = $field;
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            sprintf(
                'Update %s value to "%s" for meta field "%s" from entity "%s" to entity "%s" with relation "%s".',
                $this->key,
                var_export($this->value, true),
                $this->field,
                $this->entityFrom,
                $this->entityTo,
                $this->relationType
            )
        );

        $this->updateConfiguration($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateConfiguration($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateConfiguration(LoggerInterface $logger, $dryRun = false)
    {
        $row = $this->fetchEntityConfigRow($logger);

        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Type::TARRAY) : [];

        $fullRelationName = $this->getFullRelationName();

        if (!isset($data['extend']['relation'][$fullRelationName])) {
            $logger->warning(
                '{key} value for entity `{entity}` config field `{field}`' .
                ' was not updated as relation `{relation}` is not defined in configuration.',
                [
                    'key'      => ucfirst($this->key),
                    'entity'   => $this->entityFrom,
                    'field'    => $this->field,
                    'relation' => $fullRelationName
                ]
            );

            return;
        }

        $data['extend']['relation'][$fullRelationName][$this->key] = $this->value;

        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $this->updateEntityConfig($row['id'], $data, $logger, $dryRun);
    }

    /**
     * @param int             $id
     * @param string          $data
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateEntityConfig($id, $data, LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);

        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $statement->execute($parameters);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function fetchEntityConfigRow(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityFrom];
        $row = $this->connection->fetchAssoc($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        return $row;
    }

    /**
     * @return string
     */
    protected function getFullRelationName()
    {
        return implode('|', [$this->relationType, $this->entityFrom, $this->entityTo, $this->field]);
    }
}
