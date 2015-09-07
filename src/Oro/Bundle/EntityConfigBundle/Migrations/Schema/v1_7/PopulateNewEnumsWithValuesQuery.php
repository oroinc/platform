<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;

class PopulateNewEnumsWithValuesQuery extends ParametrizedMigrationQuery
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
            $query = sprintf(
                'INSERT INTO %s (id, name, priority, is_default) VALUES (?, ?, ?, ?)',
                $optionSet['table_name']
            );
            $class = 'stdClass';

            foreach ($optionSet['data']['extend']['set_options'] as $option) {
                $option = get_object_vars(
                    unserialize(
                        preg_replace(
                            '/^O:\d+:"[^"]++"/',
                            'O:' . strlen($class) . ':"' . $class . '"',
                            serialize($option)
                        )
                    )
                );
                $params = [
                    ExtendHelper::buildEnumValueId($option['label']),
                    $option['label'],
                    (int) $option['priority'],
                    (int) $option['is_default']
                ];
                $this->logQuery($logger, $query, $params);
                $this->connection->executeQuery($query, $params);
            }
        }
    }
}
