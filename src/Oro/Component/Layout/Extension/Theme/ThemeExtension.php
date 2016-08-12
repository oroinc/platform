<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\ContextInterface;

class ThemeExtension extends AbstractLayoutUpdateLoaderExtension
{
    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

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
        parent::__construct($resources, $provider);

        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdate($file, ContextInterface $context)
    {
        $update = $this->loader->load($file);
        if ($update) {
            $this->updates[$this->getElement($update)][] = $update;

            $this->dependencyInitializer->initialize($update);
        }
    }
}
