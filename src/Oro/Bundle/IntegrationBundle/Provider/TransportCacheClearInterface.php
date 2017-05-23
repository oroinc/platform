<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TransportCacheClearInterface
{
    /**
     * @param mixed|null $resource
     * @return void
     */
    public function cacheClear($resource = null);
}
