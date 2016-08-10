<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;

class ThemeExtension extends AbstractLayoutUpdateLoaderExtension
{
    const THEME_KEY = 'theme';

    /** @var array */
    protected $resources;

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var  array */
    protected $updates;

    /**
     * @param array $resources
     * @param LayoutUpdateLoaderInterface $loader
     * @param DependencyInitializer $dependencyInitializer
     * @param PathProviderInterface $provider
     */
    public function __construct(
        array $resources,
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $provider
    ) {
        $this->resources = $resources;
        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $this->updates = [];
        if ($context->getOr(static::THEME_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->findApplicableResources($paths);
            foreach ($files as $file) {
                $this->loadLayoutUpdate($file);
            }
        }

        return $this->updates;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function loadLayoutUpdate($file)
    {
        $update = $this->loader->load($file);
        if ($update) {
            $el = $update instanceof ElementDependentLayoutUpdateInterface
                ? $update->getElement()
                : 'root';
            $this->updates[$el][] = $update;

            $this->dependencyInitializer->initialize($update);
        }

        return $update;
    }

    /**
     * Return paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function getPaths(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        return $this->pathProvider->getPaths([]);
    }
}
