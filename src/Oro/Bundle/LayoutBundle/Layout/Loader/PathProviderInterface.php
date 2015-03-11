<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

interface PathProviderInterface
{
    const DELIMITER = '/';

    /**
     * Provides paths where with applicable resources
     *
     * @return array Array of paths imploded with delimiter
     */
    public function getPaths();
}
