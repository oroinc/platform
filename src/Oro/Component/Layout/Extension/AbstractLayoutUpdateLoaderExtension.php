<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;

abstract class AbstractLayoutUpdateLoaderExtension extends AbstractExtension
{
    const THEME_KEY = 'theme';

    /** @var array */
    protected $resources = [];

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var array */
    protected $updates = [];

    /**
     * @param array $resources
     * @param PathProviderInterface $provider
     */
    public function __construct(array $resources, PathProviderInterface $provider)
    {
        $this->resources = $resources;
        $this->pathProvider = $provider;
    }

    /**
     * @param string $file
     * @param ContextInterface $context
     */
    abstract protected function loadLayoutUpdate($file, ContextInterface $context);

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        if ($context->getOr(self::THEME_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->findApplicableResources($paths);
            foreach ($files as $file) {
                $this->loadLayoutUpdate($file, $context);
            }
        }

        return $this->updates;
    }

    /**
     * @param LayoutUpdateInterface $update
     *
     * @return string
     */
    protected function getElement(LayoutUpdateInterface $update)
    {
        return $update instanceof ElementDependentLayoutUpdateInterface ? $update->getElement() : 'root';
    }

    /**
     * Filters resources by paths
     *
     * @param array $paths
     *
     * @return array
     */
    protected function findApplicableResources(array $paths)
    {
        $result = [];
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
     * @param array $array
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
