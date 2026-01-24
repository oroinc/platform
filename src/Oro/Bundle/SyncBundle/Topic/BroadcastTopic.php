<?php

namespace Oro\Bundle\SyncBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * Implements a broadcast topic that distributes published messages to all connected clients.
 *
 * This topic handler broadcasts incoming messages to all subscribers of the topic, with support
 * for excluding specific connections and limiting delivery to eligible connections. It provides
 * a simple pub/sub mechanism where any published message is immediately distributed to all
 * interested parties, making it suitable for general-purpose notifications and updates that
 * need to reach multiple clients simultaneously.
 */
class BroadcastTopic extends AbstractTopic
{
    #[\Override]
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ) {
        $topic->broadcast($event, $exclude, $eligible);
    }
}
