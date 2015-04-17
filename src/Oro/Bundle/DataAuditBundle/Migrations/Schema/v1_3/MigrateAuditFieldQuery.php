<?php

namespace Oro\Bundle\DataAuditBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

use LogicException;

use PDO;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class MigrateAuditFieldQuery implements MigrationQuery, ConnectionAwareInterface
{
    const LIMIT = 100;

    /** @var Connection */
    private $connection;

    /** @var string[] */
    protected static $typeMap = [
        'boolean'   => 'boolean',
        'text'      => 'text',
        'string'    => 'text',
        'guid'      => 'text',
        'manyToOne' => 'text',
        'enum'      => 'text',
        'multiEnum' => 'text',
        'ref-many'  => 'text',
        'smallint'  => 'integer',
        'integer'   => 'integer',
        'bigint'    => 'integer',
        'decimal'   => 'float',
        'float'     => 'float',
        'money'     => 'float',
        'percent'   => 'float',
        'date'      =>  'date',
        'time'      => 'time',
        'datetime'  => 'datetime',
    ];

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getAuditCount() / static::LIMIT);

        $auditQb = $this->createAuditQb()
            ->setMaxResults(static::LIMIT)
        ;

        for ($i = 0; $i < $steps; $i++) {
            $rows = $auditQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAll(PDO::FETCH_ASSOC)
            ;

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    /**
     * @param array $row
     */
    private function processRow(array $row)
    {
        $fields = Type::getType(Type::TARRAY)
            ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());
        foreach ($fields as $field => $values) {
            $fieldType = $this->getFieldType($row['entity_id'], $field);
            $dataType = $this->normalizeDataTypeName($fieldType);

            $data = [
                'audit_id' => $row['id'],
                'data_type' => $dataType,
                'field' => $field,
                sprintf('old_%s', $dataType) => $this->parseValue($values['old']),
                sprintf('new_%s', $dataType) => $this->parseValue($values['new']),
            ];

            $types = [
                'integer',
                'string',
                'string',
                $dataType,
                $dataType
            ];

            $this->connection->insert('oro_audit_field', $data, $types);
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function parseValue($value)
    {
        if (isset($value['value'])) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @param int $entityId
     * @param string $field
     *
     * @return string
     */
    private function getFieldType($entityId, $field)
    {
        return $this->connection->createQueryBuilder()
            ->select('ecf.type')
            ->from('oro_entity_config_field', 'ecf')
            ->where('ecf.entity_id = :entity_id')
            ->andWhere('ecf.field_name = :field_name')
            ->setParameters([
                'entity_id' => $entityId,
                'field_name' => $field,
            ])
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return int
     */
    private function getAuditCount()
    {
        return $this->createAuditQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    private function createAuditQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('a.id AS id, a.data AS data, ec.id AS entity_id')
            ->from('oro_audit', 'a')
            ->join('a', 'oro_entity_config', 'ec', 'a.object_class = ec.class_name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Copy audit data int oro_audit_field table.';
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $dataType
     *
     * @return string|null
     */
    private function normalizeDataTypeName($dataType)
    {
        if (isset(static::$typeMap[$dataType])) {
            return static::$typeMap[$dataType];
        }

        throw new LogicException(sprintf('Cannot normalize type "%s".', $dataType));
    }
}
