<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface ConnectorInterface extends ConnectorTypeInterface
{
    const SYNC_DIRECTION_PULL = 'pull';
    const SYNC_DIRECTION_PUSH = 'push';

    /**
     * Init connection using transport
     *
     * @return mixed
     */
    public function connect();

    /**
     * Called by objects with ReaderInterface
     *
     * @return mixed
     */
    public function read();
}
