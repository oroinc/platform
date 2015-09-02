<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;

class ConvertOptionSetsToEnumsQuery extends ParametrizedMigrationQuery
{
    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    protected $storage;

    public function __construct(EntityMetadataHelper $metadataHelper, $optionSets)
    {
        $this->metadataHelper = $metadataHelper;
        $this->storage = $optionSets;
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
        foreach ($this->storage as $optionSet) {
            $query = sprintf('INSERT INTO %s (id, name, priority, is_default) VALUES (?, ?, ?, ?)', 'oro_enum_' . $optionSet['table_name']);
            $class = 'stdClass';

            $i = 0;
            foreach ($optionSet['data']['extend']['set_options'] as $option) {
                $option = get_object_vars(unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($option))));
                $params = [$i++, $option['label'], $option['priority'], empty($option['is_default']) ?  0 : $option['is_default']];
                $this->logQuery($logger, $query, $params);
                $this->connection->executeQuery($query, $params);
            }
        }
    }
}