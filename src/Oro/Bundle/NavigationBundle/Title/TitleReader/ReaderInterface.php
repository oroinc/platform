<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

interface ReaderInterface
{
    /**
     * Returns data from source
     *
     * @param  array $routes
     * @return array
     */
    public function getData(array $routes);

    /**
     * Check if data is fresh
     *
     * @return boolean
     */
//    public function isFresh();
}
