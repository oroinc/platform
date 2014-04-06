<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResourceInfo;

/**
 * CumulativeResourceLoader is the interface that must be implemented by all resource loader classes
 * responsible to load resources which can be located in any bundle and does not required any special
 * registration in a bundle.
 */
interface CumulativeResourceLoader
{
    /**
     * Gets the resource
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Loads the resource located in the given bundle
     *
     * @param string $bundleClass
     * @param string $bundleDir
     * @return CumulativeResourceInfo|null
     */
    public function load($bundleClass, $bundleDir);

    /**
     * Adds a resource object to the container.
     * This object will be used to check whether the resource loaded by this loader is up-to-date or not.
     *
     * @param ContainerBuilder $container
     */
    public function registerResource(ContainerBuilder $container);
}
