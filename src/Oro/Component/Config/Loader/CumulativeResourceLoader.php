<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;

/**
 * CumulativeResourceLoader is the interface that must be implemented by all resource loader classes
 * responsible to load resources which can be located in any bundle and does not required any special
 * registration in a bundle.
 */
interface CumulativeResourceLoader extends \Serializable
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
     * @param string $bundleClass  The full name of bundle class
     * @param string $bundleDir    The bundle root directory
     * @param string $bundleAppDir The bundle directory inside the application resources directory
     *
     * @return CumulativeResourceInfo|CumulativeResourceInfo[]|null
     */
    public function load($bundleClass, $bundleDir, $bundleAppDir = '');

    /**
     * Registers the resource located in the given bundle as found.
     *
     * @param string             $bundleClass  The full name of bundle class
     * @param string             $bundleDir    The bundle root directory
     * @param string             $bundleAppDir The bundle directory inside the application resources directory
     * @param CumulativeResource $resource
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource);

    /**
     * Returns true if the resource loaded by this loader and located in the given bundle
     * has not been updated since the given timestamp.
     *
     * @param string             $bundleClass  The full name of bundle class
     * @param string             $bundleDir    The bundle root directory
     * @param string             $bundleAppDir The bundle directory inside the application resources directory
     * @param CumulativeResource $resource     The resource
     * @param int                $timestamp    The last time the resource was loaded
     *
     * @return bool TRUE if the resource has not been updated; otherwise, FALSE
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp);
}
