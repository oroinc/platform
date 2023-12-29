<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;

/**
 * This trait can be used by migrations and migration queries that implement {@see ConnectionAwareInterface}.
 */
trait ConnectionAwareTrait
{
    private Connection $connection;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }
}
