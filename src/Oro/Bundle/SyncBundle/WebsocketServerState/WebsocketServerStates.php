<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\WebsocketServerState;

/**
 * Contains websocket server state identifiers available out-of-the-box.
 */
final class WebsocketServerStates
{
    public const string SYSTEM_CONFIG = 'system_config';
    public const string APPLICATION_CACHE = 'application_cache';
}
