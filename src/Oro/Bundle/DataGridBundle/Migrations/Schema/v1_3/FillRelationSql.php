<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Log\LoggerInterface;

class FillRelationSql extends ParametrizedMigrationQuery
{
    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->fillRelations($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->fillRelations($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fillRelations(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'INSERT INTO oro_grid_view_user_rel (grid_view_id, user_id, alias, grid_name)
            SELECT ogvu.grid_view_id, ogvu.user_id, ogv.name, ogv.gridName
            FROM
                oro_grid_view_user AS ogvu
            LEFT JOIN oro_grid_view as ogv ON ogvu.grid_view_id = ogv.id;';
        $params = [];
        $types  = [];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }
}
