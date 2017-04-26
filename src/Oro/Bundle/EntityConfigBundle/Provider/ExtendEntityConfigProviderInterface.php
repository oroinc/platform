<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

interface ExtendEntityConfigProviderInterface
{
    /**
     * @param null|callable $filter
     * @return ConfigInterface[]
     */
    public function getExtendEntityConfigs($filter = null);

    /**
     * Sets service to work only with attributes otherwise all extend configs will be always returned
     * @return $this
     */
    public function enableAttributesOnly();
}
