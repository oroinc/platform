<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\RouteFileResource;

class ThemeExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const ROUTE_CONTEXT_PARAM = 'routeName';

    /** @var array */
    protected $resources;

    /** @var ThemeManager */
    protected $manager;

    /** @var LoaderInterface */
    private $loader;

    /**
     * @param array           $resources
     * @param ThemeManager    $manager
     * @param LoaderInterface $loader
     */
    public function __construct(array $resources, ThemeManager $manager, LoaderInterface $loader)
    {
        $this->resources = $resources;
        $this->manager   = $manager;
        $this->loader    = $loader;
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates()
    {
        $updates   = $skipped = [];
        $theme     = $this->manager->getTheme();
        $directory = $theme->getDirectory();

        $themeResources = isset($this->resources[$directory]) ? $this->resources[$directory] : [];
        foreach ($themeResources as $routeName => $resources) {
            // work with global resources in the same way as with route related
            $resources = is_array($resources) ? $resources : [$resources];

            foreach ($resources as $resource) {
                $resource = is_string($routeName)
                    ? new RouteFileResource($resource, $routeName)
                    : new FileResource($resource);

                if ($this->loader->supports($resource)) {
                    $updates[] = $this->loader->load($resource);
                } else {
                    $skipped[] = $resource;
                }
            }
        }

        array_walk($skipped, [$this, 'logUnknownResource']);

        return ['root' => $updates];
    }

    /**
     * @param FileResource $resource
     */
    protected function logUnknownResource(FileResource $resource)
    {
        $this->logger->notice(sprintf('Skipping resource "%s" because loader for it not found', $resource));
    }
}
