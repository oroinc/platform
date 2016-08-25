<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

class ThemeResourceProvider implements ResourceProviderInterface
{
    /** @var array */
    private $resources;

    /**
     * @param array $resources
     */
    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function findApplicableResources(array $paths)
    {
        $result = [];

        foreach ($paths as $path) {
            $pathArray = explode(DIRECTORY_SEPARATOR, $path);

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
     * @return array|null
     */
    private function readValue($array, $property)
    {
        if (is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
