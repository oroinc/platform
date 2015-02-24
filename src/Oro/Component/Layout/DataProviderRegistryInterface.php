<?php

namespace Oro\Component\Layout;

interface DataProviderRegistryInterface extends \ArrayAccess
{
    /**
     * Returns a data provider by name.
     *
     * @param string $name The name of the data provider
     *
     * @return DataProviderInterface
     *
     * @throws Exception\InvalidArgumentException if a data provider cannot be loaded
     */
    public function get($name);
}
