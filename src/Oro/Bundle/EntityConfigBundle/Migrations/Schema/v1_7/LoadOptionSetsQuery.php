<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\DataStorageInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class LoadOptionSetsQuery extends ParametrizedMigrationQuery
{
    /** @var DataStorageInterface */
    protected $storage;

    /**
     * @param DataStorageInterface $storage
     */
    public function __construct(DataStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Retrieve existing option sets');
        $this->execute($logger);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $optionSets      = $this->loadOptionSets($logger);
        $optionSetValues = $this->loadOptionSetValues($logger);
        $assignments     = $this->loadOptionSetAssignments($logger);
        foreach ($optionSets as &$optionSet) {
            $fieldId = $optionSet['field_id'];
            $values  = isset($optionSetValues[$fieldId]) ? $optionSetValues[$fieldId] : [];
            foreach ($values as &$value) {
                $optionId = $value['option_id'];
                if (isset($assignments[$fieldId][$optionId])) {
                    $value['assignments'][] = $assignments[$fieldId][$optionId];
                }
            }

            $optionSet['values'] = $values;
            $optionSet['data']   = $this->connection->convertToPHPValue($optionSet['data'], 'array');
        }

        $this->storage->set('existing_option_sets', $optionSets);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadOptionSets(LoggerInterface $logger)
    {
        $query = 'SELECT c.id AS config_id, f.id AS field_id, '
            . 'c.class_name, f.field_name, f.data '
            . 'FROM oro_entity_config_field AS f '
            . 'INNER JOIN oro_entity_config AS c ON f.entity_id = c.id '
            . 'WHERE type = ?';

        $params = ['optionSet'];

        $this->logQuery($logger, $query, $params);
        $rows = $this->connection->fetchAll($query, $params);

        foreach ($rows as &$row) {
            $row['config_id'] = (int)$row['config_id'];
            $row['field_id']  = (int)$row['field_id'];
        }

        return $rows;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadOptionSetValues(LoggerInterface $logger)
    {
        $query = 'SELECT o.field_id, o.id AS option_id, o.label, o.priority, o.is_default '
            . 'FROM oro_entity_config_optionset o';

        $this->logQuery($logger, $query);
        $rows = $this->connection->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $fieldId = $row['field_id'];
            unset($row['field_id']);
            $row['priority']   = (int)$row['priority'];
            $row['is_default'] = (int)$row['is_default'];

            $result[$fieldId][] = $row;
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadOptionSetAssignments(LoggerInterface $logger)
    {
        $query = 'SELECT rel.field_id, rel.option_id, rel.entity_id '
            . 'FROM oro_entity_config_optset_rel rel';

        $this->logQuery($logger, $query);
        $rows = $this->connection->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $fieldId  = $row['field_id'];
            $optionId = $row['option_id'];

            $result[$fieldId][$optionId] = (int)$row['entity_id'];
        }

        return $result;
    }
}
