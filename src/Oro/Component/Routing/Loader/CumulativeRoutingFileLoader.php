<?php

namespace Oro\Component\Routing\Loader;

use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Loads routing definitions from cumulative resource files across all bundles.
 *
 * This loader discovers and loads routing configuration files from specified
 * relative paths within each bundle, allowing for distributed route definitions
 * across the application's bundle structure.
 */
class CumulativeRoutingFileLoader extends AbstractLoader
{
    /** @var KernelInterface */
    protected $kernel;

    /** @var string[] */
    protected $relativeFilePaths;

    /** @var string */
    protected $routeType;

    /**
     * @param KernelInterface               $kernel               The application kernel
     * @param RouteOptionsResolverInterface $routeOptionsResolver The route options resolver
     * @param string[]                      $relativeFilePaths    The list of the relative paths
     *                                                            to routing definition files
     *                                                            starts from bundle folder
     * @param string                        $routeType            The type of the route supported by this loader
     */
    public function __construct(
        KernelInterface $kernel,
        RouteOptionsResolverInterface $routeOptionsResolver,
        $relativeFilePaths,
        $routeType
    ) {
        parent::__construct($routeOptionsResolver);

        $this->kernel            = $kernel;
        $this->relativeFilePaths = $relativeFilePaths;
        $this->routeType         = $routeType;
    }

    #[\Override]
    public function supports($resource, $type = null): bool
    {
        return $type === $this->routeType;
    }

    #[\Override]
    protected function getResources()
    {
        $resources = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            foreach ($this->relativeFilePaths as $relativeFilePath) {
                $path = $bundle->getPath() . DIRECTORY_SEPARATOR . $relativeFilePath;
                if (file_exists($path)) {
                    $resources[] = $path;
                }
            }
        }

        return $resources;
    }
}
