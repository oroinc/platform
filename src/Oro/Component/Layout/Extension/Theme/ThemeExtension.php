<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\Model\ResourceIterator;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;

class ThemeExtension extends AbstractExtension
{
    /** @var array */
    protected $resources;

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /**
     * @param array                       $resources
     * @param LayoutUpdateLoaderInterface $loader
     * @param DependencyInitializer       $dependencyInitializer
     * @param PathProviderInterface       $provider
     */
    public function __construct(
        array $resources,
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $provider
    ) {
        $this->resources             = $resources;
        $this->loader                = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider          = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $updates = [];

        if ($context->getOr('theme')) {
            $iterator = new ResourceIterator($this->findApplicableResources($context));
            foreach ($iterator as $file) {
                $update = $this->loader->load($file);
                if ($update) {
                    $this->dependencyInitializer->initialize($update);
                    $el             = $update instanceof ElementDependentLayoutUpdateInterface
                        ? $update->getElement()
                        : 'root';
                    $updates[$el][] = $update;
                }
            }
        }

        return $updates;
    }

    /**
     * Filters resources by paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function findApplicableResources(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        $result = [];
        $paths  = $this->pathProvider->getPaths([]);
        foreach ($paths as $path) {
            $pathArray = explode(PathProviderInterface::DELIMITER, $path);

            $value = $this->resources;
            for ($i = 0, $length = count($pathArray); $i < $length; ++$i) {
                $value = $this->readValue($value, $pathArray[$i]);

                if (null === $value) {
                    break;
                }
            }

            if ($value && is_array($value)) {
                $result = array_merge($result, array_filter($value, 'is_string'));
            }
        }

        return $result;
    }

    /**
     * @param array  $array
     * @param string $property
     *
     * @return mixed
     */
    protected function readValue(&$array, $property)
    {
        if (is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
