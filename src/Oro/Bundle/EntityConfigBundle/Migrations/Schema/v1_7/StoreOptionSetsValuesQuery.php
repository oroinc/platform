<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class StoreOptionSetsValuesQuery extends ParametrizedMigrationQuery
{
    /** @var DataStorageExtension */
    protected $storage;

    public function __construct(DataStorageExtension $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Retrieve entities option sets values';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $query = 'SELECT oec.class_name, oecor.entity_id AS row_id, oecf.field_name, '
            . 'GROUP_CONCAT(REPLACE(oeco.label, ",", "")) as labels, oecf.id as field_id '
            . 'FROM oro_entity_config_optset_rel oecor '
            . 'LEFT JOIN oro_entity_config_field oecf ON oecor.field_id = oecf.id '
            . 'LEFT JOIN oro_entity_config oec ON oecf.entity_id=oec.id '
            . 'LEFT JOIN oro_entity_config_optionset oeco ON oeco.id = oecor.option_id AND oeco.field_id = oecor.field_id '
            . 'GROUP BY class_name, row_id, field_name';

        $this->logQuery($logger, $query);
        $optionSetsValues = $this->connection->fetchAll($query);

        $this->storage->put('existing_option_sets_values', $optionSetsValues);
    }
}
