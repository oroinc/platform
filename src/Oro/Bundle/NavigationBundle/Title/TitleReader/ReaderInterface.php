<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

/**
 * Defines the contract for reading page titles from configuration sources.
 *
 * Implementations of this interface are responsible for retrieving page titles from various sources
 * such as configuration files, databases, or other data stores. Different readers can support different
 * title sources, allowing flexible title management across the application.
 */
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
