<?php

namespace Oro\Component\Layout;

interface DataAccessorInterface extends \ArrayAccess
{
    /**
     * Returns an unique identifier of data by the name of the data provider.
     *
     * @param string $name The name of the data provider
     *
     * @return string
     *
     * @throws Exception\InvalidArgumentException if the data provider cannot be loaded
     */
    public function getIdentifier($name);

    /**
     * Returns data by the name of the data provider.
     *
     * @param string $name The name of the data provider
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException if the data provider cannot be loaded
     */
    public function get($name);
}
