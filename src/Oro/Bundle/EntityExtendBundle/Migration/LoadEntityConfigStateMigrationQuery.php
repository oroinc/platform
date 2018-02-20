<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\DataStorageInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class LoadEntityConfigStateMigrationQuery extends ParametrizedMigrationQuery
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
        $this->loadEntityConfigStates($logger);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->loadEntityConfigStates($logger);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function loadEntityConfigStates(LoggerInterface $logger)
    {
        if (!$this->connection->getSchemaManager()->tablesExist(['oro_entity_config', 'oro_entity_config_field'])) {
            return;
        }

        $entityConfigs = [];
        $sql           = 'SELECT e.class_name, e.data FROM oro_entity_config e';
        $this->logQuery($logger, $sql);
        foreach ($this->connection->fetchAll($sql) as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (isset($data['extend']['state']) && $data['extend']['state'] !== ExtendScope::STATE_ACTIVE) {
                $entityConfigs[$row['class_name']] = $data['extend']['state'];
            }
        }

        $fieldConfigs = [];
        $sql          = 'SELECT e.class_name, f.field_name, f.data '
            . 'FROM oro_entity_config e '
            . 'INNER JOIN oro_entity_config_field f ON f.entity_id = e.id';
        $this->logQuery($logger, $sql);
        foreach ($this->connection->fetchAll($sql) as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (isset($data['extend']['state']) && $data['extend']['state'] !== ExtendScope::STATE_ACTIVE) {
                $fieldConfigs[$row['class_name']][$row['field_name']] = $data['extend']['state'];
            }
        }

        $this->storage->set(
            'initial_entity_config_state',
            ['entities' => $entityConfigs, 'fields' => $fieldConfigs]
        );
    }
}
