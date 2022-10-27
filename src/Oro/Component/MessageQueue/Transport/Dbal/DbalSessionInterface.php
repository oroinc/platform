<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * A Session object for DBAL connection.
 */
interface DbalSessionInterface extends SessionInterface
{
    public function getConnection(): ConnectionInterface;
}
