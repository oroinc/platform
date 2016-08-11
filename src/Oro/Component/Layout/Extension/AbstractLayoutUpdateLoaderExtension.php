<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

abstract class AbstractLayoutUpdateLoaderExtension extends AbstractExtension
{
    /** @var array */
    protected $resources;

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
}
