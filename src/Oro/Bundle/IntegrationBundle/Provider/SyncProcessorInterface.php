<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface SyncProcessorInterface
{
    /**
     * @param $batchData
     * @return mixed
     */
    public function process($batchData);
}
