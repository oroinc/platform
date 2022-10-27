<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Provides interface for MigrationQueryExecutor
 */
interface MigrationQueryExecutorInterface
{
    public function getConnection():Connection;

    public function execute($query, $dryRun): void;

    public function setLogger(LoggerInterface $logger): void;
}
