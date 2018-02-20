<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema\v1_1;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveApplicableAttributeQuery extends ParametrizedMigrationQuery
{
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        foreach ($this->getAllConfigurableEntities($logger) as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (isset($data['comment']['applicable'])) {
                unset($data['comment']['applicable']);
                $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $row['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return string[]
     */
    protected function getAllConfigurableEntities(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        return $this->connection->fetchAll($sql);
    }
}
