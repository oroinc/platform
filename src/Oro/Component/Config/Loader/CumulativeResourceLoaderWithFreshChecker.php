<?php

namespace Oro\Component\Config\Loader;

/**
 * CumulativeResourceChecker is the interface that must be implemented by resource loader classes
 * which takes responsibility to check whether loaded resources are up-to-date or not
 */
interface CumulativeResourceLoaderWithFreshChecker extends CumulativeResourceLoader
{
    /**
     * Returns true if the resource loaded by this loader and located in the given bundle
     * has not been updated since the given timestamp.
     *
     * @param string $bundleClass
     * @param string $bundleDir
     * @param int    $timestamp The last time the resource was loaded
     *
     * @return bool TRUE if the resource has not been updated; otherwise, FALSE
     */
    public function isResourceFresh($bundleClass, $bundleDir, $timestamp);
}
