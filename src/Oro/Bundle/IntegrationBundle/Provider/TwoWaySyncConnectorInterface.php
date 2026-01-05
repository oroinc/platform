<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TwoWaySyncConnectorInterface extends ConnectorInterface
{
    public const REMOTE_WINS = 'remote';
    public const LOCAL_WINS  = 'local';

    /**
     * @return string
     */
    public function getExportJobName();
}
