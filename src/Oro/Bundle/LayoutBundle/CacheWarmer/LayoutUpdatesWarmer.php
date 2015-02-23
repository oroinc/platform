<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\RouteFileResource;

class LayoutUpdatesWarmer implements CacheWarmerInterface
{
    /** @var LoaderInterface */
    protected $loader;

    /** @var array */
    protected $resources;

    /**
     * @param array           $resources
     * @param LoaderInterface $loader
     */
    public function __construct(array $resources, LoaderInterface $loader)
    {
        $this->loader    = $loader;
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        // TODO refactor copy/paste, possible solve with iterator
        // TODO cover by test
        // TODO allow loader to load without referencing on update object

        foreach ($this->resources as $themeResources) {
            foreach ($themeResources as $routeName => $resources) {
                // work with global resources in the same way as with route related
                $resources = is_array($resources) ? $resources : [$resources];

                foreach ($resources as $resource) {
                    $resource = is_string($routeName)
                        ? new RouteFileResource($resource, $routeName)
                        : new FileResource($resource);

                    try {
                        $this->loader->load($resource);
                    } catch (\Exception $e) {
                        // problem during compilation, give up
                    }
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
