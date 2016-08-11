<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;

abstract class AbstractLayoutUpdateLoaderExtension extends AbstractExtension
{
    /** @var array */
    protected $resources;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var  array */
    protected $updates;

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $this->updates = [];
        if ($context->getOr(ThemeExtension::THEME_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->findApplicableResources($paths);
            foreach ($files as $file) {
                $this->loadLayoutUpdate($file, $context);
            }
        }

        return $this->updates;
    }

    /**
     * Filters resources by paths
     *
     * @param array $paths
     *
     * @return array
     */
    public function findApplicableResources(array $paths)
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
    public function readValue(&$array, $property)
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

    /**
     * @param string $file
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function loadLayoutUpdate($file, ContextInterface $context)
    {
        return [];
    }
}
