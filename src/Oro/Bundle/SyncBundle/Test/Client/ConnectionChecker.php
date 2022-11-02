<?php

namespace Oro\Bundle\SyncBundle\Test\Client;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker as BaseConnectionChecker;

/**
 * Extends {@see BaseConnectionChecker} to disable websocket connection checks.
 */
class ConnectionChecker extends BaseConnectionChecker
{
    public function checkConnection(): bool
    {
        return false;
    }
}
