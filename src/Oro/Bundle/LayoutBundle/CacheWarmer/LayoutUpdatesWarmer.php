<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceIterator;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface;

class LayoutUpdatesWarmer implements CacheWarmerInterface
{
    /** @var array */
    protected $resources;

    /** @var LoaderInterface */
    protected $loader;

    /** @var ResourceFactoryInterface */
    protected $factory;

    /**
     * @param array                    $resources
     * @param ResourceFactoryInterface $factory
     * @param LoaderInterface          $loader
     */
    public function __construct(array $resources, ResourceFactoryInterface $factory, LoaderInterface $loader)
    {
        $this->resources = $resources;
        $this->factory   = $factory;
        $this->loader    = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach (new ResourceIterator($this->factory, $this->resources) as $resource) {
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
