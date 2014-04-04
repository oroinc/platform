<?php

namespace Oro\Bundle\CacheBundle\Config\Loader;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface CumulativeLoaderHolder
{
    /**
     * @return BundleInterface[]
     */
    public function getBundles();
}
