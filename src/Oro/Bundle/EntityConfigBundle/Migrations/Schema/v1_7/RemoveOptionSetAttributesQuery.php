<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveOptionSetAttributesQuery extends ParametrizedMigrationQuery
{
    /** @var int[] */
    protected $configFieldIds;

    /**
     * @param int[] $configFieldIds
     */
    public function __construct(array $configFieldIds)
    {
        $this->configFieldIds = $configFieldIds;
    }

    /**
     * {inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->removeOptionSetAttributes($logger, true);

        return $logger->getMessages();
    }

    /**
     * {inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->removeOptionSetAttributes($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function removeOptionSetAttributes(LoggerInterface $logger, $dryRun = false)
    {
        if (empty($this->configFieldIds)) {
            return;
        }

        $configs = $this->loadConfigs($this->configFieldIds);
        foreach ($configs as $id => $data) {
            unset($data['extend']['set_expanded']);
            unset($data['extend']['set_options']);

            $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $id];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
        }
    }

    /**
     * @param int[] $configFieldIds
     *
     * @return array
     */
    protected function loadConfigs($configFieldIds)
    {
        $query = sprintf(
            'SELECT id, data FROM oro_entity_config_field WHERE id IN (%s)',
            implode(',', $configFieldIds)
        );
        $rows  = $this->connection->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
