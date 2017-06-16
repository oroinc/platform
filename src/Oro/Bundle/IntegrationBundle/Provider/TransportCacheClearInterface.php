<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TransportCacheClearInterface
{
    /**
     * @param mixed|null $url
     *
     * @return void
     */
    public function cacheClear($url = null);
}
