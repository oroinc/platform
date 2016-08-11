<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\ContextInterface;

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
     * @param string $file
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function loadLayoutUpdate($file, ContextInterface $context)
    {
        $update = $this->loader->load($file);
        //var_dump($this->updates);
        if ($update) {
            $el = $update instanceof ElementDependentLayoutUpdateInterface
                ? $update->getElement()
                : 'root';
            $this->updates[$el][] = $update;

            $this->dependencyInitializer->initialize($update);
        }

        return $update;
    }
}
