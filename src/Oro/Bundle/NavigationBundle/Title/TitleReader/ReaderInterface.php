<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

interface ReaderInterface
{
    /**
     * Returns title for current route name from source if exist
     *
     * @param string $route
     *
     * @return string|null
     */
    public function getTitle($route);
}
