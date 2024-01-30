<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Represents an executor of migrations queries.
 */
interface MigrationQueryExecutorInterface
{
    public function getConnection(): Connection;

    public function execute($query, $dryRun): void;

    public function setLogger(LoggerInterface $logger): void;
}
