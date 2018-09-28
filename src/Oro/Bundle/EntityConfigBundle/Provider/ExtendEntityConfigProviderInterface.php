<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Interface for extend entity config providers
 */
interface ExtendEntityConfigProviderInterface
{
    /**
     * @param null|callable $filter
     * @return ConfigInterface[]
     */
    public function getExtendEntityConfigs($filter = null);
}
