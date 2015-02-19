<?php

namespace Oro\Bundle\LayoutBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\YamlFileLoader;

class YamlUpdatesWarmer implements CacheWarmerInterface
{
    /** @var YamlFileLoader */
    protected $loader;

    /** @var array */
    protected $resources;

    /**
     * @param array          $resources
     * @param YamlFileLoader $loader
     */
    public function __construct(array $resources, YamlFileLoader $loader)
    {
        $this->loader    = $loader;
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $iterator = new \RecursiveArrayIterator($this->resources);

        foreach (new \RecursiveIteratorIterator($iterator) as $resource) {
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

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
