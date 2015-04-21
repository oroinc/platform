<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface ForceConnectorInterface
{
    /**
     * Returns whether connector supports force sync or no
     *
     * @return bool
     */
    public function supportsForceSync();
}
