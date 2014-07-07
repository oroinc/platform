<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TwoWaySyncConnectorInterface
{
    const REMOTE_WINS = 'remote';
    const LOCAL_WINS  = 'local';

    /**
     * @return string
     */
    public function getExportJobName();
}
