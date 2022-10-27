<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Client\MessageBufferManager;

/**
 * This class is used only in functional tests and it is required because
 * functional tests are wrapped with an external DBAL transaction
 * as a result of dbIsolation and dbIsolationPerTest annotations.
 */
class TestMessageBufferManager extends MessageBufferManager
{
    /**
     * {@inheritDoc}
     */
    protected function isTransactionActive(Connection $connection): bool
    {
        return $connection->getTransactionNestingLevel() === 1;
    }
}
