<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class StoreOptionSetsQuery extends ParametrizedMigrationQuery
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
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $query = 'SELECT f.*, c.class_name, c.id config_id FROM oro_entity_config_field AS f '
            . 'LEFT JOIN oro_entity_config AS c ON f.entity_id = c.id '
            . 'WHERE type = ?';

        $params = ['optionSet'];

        $this->logQuery($logger, $query, $params);
        $optionSets = $this->connection->fetchAll($query, $params);

        $type = Type::getType(Type::TARRAY);
        $platform = $this->connection->getDatabasePlatform();

        $data = [];
        foreach ($optionSets as $optionSet) {
            $optionSet['data'] = $type->convertToPHPValue($optionSet['data'], $platform);

            $data[] = $optionSet;
        }

        $this->storage->put('existing_option_sets', $data);
    }
}