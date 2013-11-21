<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface SyncProcessorInterface
{
    /**
     * @param $channelId
     * @return mixed
     */
    public function process($channelId);
}
