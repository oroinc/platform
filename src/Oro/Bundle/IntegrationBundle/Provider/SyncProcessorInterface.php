<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface SyncProcessorInterface
{
    /**
     * @return mixed
     */
    public function process();
}
