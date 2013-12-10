<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

interface ConnectorInterface extends ConnectorTypeInterface
{
    const SYNC_DIRECTION_PULL = 'pull';
    const SYNC_DIRECTION_PUSH = 'push';

    /**
     * Configure connector with given transport worker and it's settings
     *
     * @param TransportInterface $realTransport
     * @param Transport          $transportSettings
     *
     * @return void
     */
    public function configure(TransportInterface $realTransport, Transport $transportSettings);

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
