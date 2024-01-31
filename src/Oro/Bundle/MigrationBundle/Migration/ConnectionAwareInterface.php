<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;

/**
 * This interface should be implemented by migrations and migration queries that depend on a database connection.
 */
interface ConnectionAwareInterface
{
    public function setConnection(Connection $connection);
}
