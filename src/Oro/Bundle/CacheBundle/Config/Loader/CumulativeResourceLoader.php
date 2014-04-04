<?php

namespace Oro\Bundle\CacheBundle\Config\Loader;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;

interface CumulativeResourceLoader
{
    /**
     * Gets resource name
     *
     * @return string
     */
    public function getResource();

    /**
     * Loads resource located in the given bundle
     *
     * @param BundleInterface $bundle
     * @return CumulativeResourceInfo|null
     */
    public function load(BundleInterface $bundle);

    /**
     * Returns true if the resource has not been updated since the given timestamp.
     *
     * @param BundleInterface $bundle
     * @param integer         $timestamp The last time the resource was loaded
     *
     * @return Boolean true if the resource has not been updated, false otherwise
     */
    public function isFresh(BundleInterface $bundle, $timestamp);
}
