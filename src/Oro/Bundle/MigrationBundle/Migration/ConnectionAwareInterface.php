<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;

/**
 * ConnectionAwareInterface should be implemented by migration queries that depends on a database connection.
 */
interface ConnectionAwareInterface
{
    /**
     * Sets the database connection
     *
     * @param Connection $connection
     */
    public function setConnection(Connection $connection);
}
