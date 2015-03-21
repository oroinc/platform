<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface;
use Oro\Component\Layout\Extension\Theme\Loader\ResourceIterator;

class LayoutUpdatesWarmer implements CacheWarmerInterface
{
    /** @var array */
    protected $resources;

    /** @var LoaderInterface */
    protected $loader;

    /**
     * @param array           $resources
     * @param LoaderInterface $loader
     */
    public function __construct(array $resources, LoaderInterface $loader)
    {
        $this->resources = $resources;
        $this->loader    = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach (new ResourceIterator($this->resources) as $resource) {
            if (!$this->loader->supports($resource)) {
                continue;
            }

            $this->loader->load($resource);
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
