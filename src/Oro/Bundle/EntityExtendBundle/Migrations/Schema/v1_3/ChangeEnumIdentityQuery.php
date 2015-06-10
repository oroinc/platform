<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

use Doctrine\DBAL\Connection;

class ChangeEnumIdentityQuery extends ParametrizedMigrationQuery
{
    const FIELD_FROM = 'name';
    const FIELD_TO   = 'id';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $enumEntityIds = $this->getEnumEntityIds($logger);

        if (empty($enumEntityIds)) {
            return;
        }

        $enumEntities  = $this->getEnumEntitiesWithFields($logger, $enumEntityIds);

        $this->processChange($logger, $enumEntities, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getEnumEntityIds(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $rows = $this->connection->fetchAll($sql);

        $entities = [];

        foreach ($rows as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            if (array_key_exists('extend', $data)
                && array_key_exists('inherit', $data['extend'])
                && $data['extend']['inherit'] == 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
            ) {
                $entities[] = $row['id'];
            }
        }

        return $entities;
    }

    /**
     * @param LoggerInterface $logger
     * @param array $entityIds
     *
     * @return array
     */
    protected function getEnumEntitiesWithFields(LoggerInterface $logger, array $entityIds)
    {
        $sql = 'SELECT fc.id, fc.entity_id, fc.field_name, fc.data FROM oro_entity_config ec '
            . 'INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id AND ec.id IN ('
            . implode(', ', $entityIds)
            . ') WHERE fc.field_name IN (:field_from, :field_to)';
        $params = ['field_from' => self::FIELD_FROM, 'field_to' => self::FIELD_TO];

        $this->logQuery($logger, $sql, $params);

        $rows = $this->connection->fetchAll($sql, $params);

        $entities = [];

        foreach ($rows as $row) {
            $entities[$row['entity_id']][$row['field_name']] = [
                'id'         => $row['id'],
                'entity_id'  => $row['entity_id'],
                'field_name' => $row['field_name'],
                'data'       => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $entities;
    }

    /**
     * @param LoggerInterface $logger
     * @param array $entities
     * @param bool $dryRun
     */
    protected function processChange(LoggerInterface $logger, array $entities, $dryRun = false)
    {
        foreach ($entities as $enumEntity) {
            if (array_key_exists(self::FIELD_FROM, $enumEntity)
                && array_key_exists('data', $enumEntity[self::FIELD_FROM])
                && array_key_exists('importexport', $enumEntity[self::FIELD_FROM]['data'])
                && array_key_exists('identity', $enumEntity[self::FIELD_FROM]['data']['importexport'])
                && array_key_exists(self::FIELD_TO, $enumEntity)
                && array_key_exists('data', $enumEntity[self::FIELD_TO])
            ) {
                // remove identity
                unset($enumEntity[self::FIELD_FROM]['data']['importexport']['identity']);

                // add identity
                $enumEntity[self::FIELD_TO]['data']['importexport']['identity'] = true;

                $sql = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';

                foreach ($enumEntity as $enumEntityField) {
                    $params = ['data' => $enumEntityField['data'], 'id' => $enumEntityField['id']];
                    $types  = ['data' => 'array', 'id' => 'integer'];

                    $this->logQuery($logger, $sql, $params, $types);

                    if (!$dryRun) {
                        $this->connection->executeUpdate($sql, $params, $types);
                    }
                }
            }
        }
    }
}
