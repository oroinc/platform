<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Extension\Theme\Model\ResourceIterator;

class LayoutUpdatesWarmer implements CacheWarmerInterface
{
    /** @var array */
    protected $resources;

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /**
     * @param array                       $resources
     * @param LayoutUpdateLoaderInterface $loader
     */
    public function __construct(array $resources, LayoutUpdateLoaderInterface $loader)
    {
        $this->resources = $resources;
        $this->loader    = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $iterator = new ResourceIterator($this->resources);
        foreach ($iterator as $file) {
            $this->loader->load($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
