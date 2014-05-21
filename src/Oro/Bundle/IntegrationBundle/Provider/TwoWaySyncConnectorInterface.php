<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TwoWaySyncConnectorInterface
{
    /**
     * @return string
     */
    public function getExportJobName();
}
