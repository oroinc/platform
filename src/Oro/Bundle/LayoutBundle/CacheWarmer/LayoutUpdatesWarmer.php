<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeResourceIterator;
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
        foreach ($this->resources as $themeResources) {
            foreach (new ThemeResourceIterator($this->factory, $themeResources) as $resource) {
                if (!$this->loader->supports($resource)) {
                    continue;
                }

                try {
                    $this->loader->load($resource);
                } catch (\Exception $e) {
                    // problem during compilation, give up
                }
            }
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
