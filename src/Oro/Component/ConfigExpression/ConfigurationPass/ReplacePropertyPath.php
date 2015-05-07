<?php

namespace Oro\Component\ConfigExpression\ConfigurationPass;

use Oro\Component\PropertyAccess\PropertyPath;

/**
 * Passes through configuration array and replaces parameter strings ($parameter.name)
 * with appropriate PropertyPath objects.
 * To pass a string starts with $ just add \ before $, for example
 * \$parameter.name is converted to string "$parameter.name" rather than PropertyPath object.
 */
class ReplacePropertyPath implements ConfigurationPassInterface
{
    /** @var PropertyPath[] */
    private $cache = [];

    /** @var string */
    protected $prefix;

    /**
     * {@inheritdoc}
     */
    public function passConfiguration(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->passConfiguration($value);
            } elseif (is_string($value)) {
                $pos = strpos($value, '$');
                if ($pos === 0) {
                    if (isset($this->cache[$value])) {
                        $propertyPath = $this->cache[$value];
                    } else {
                        $propertyPath        = $this->parsePropertyPath($value);
                        $this->cache[$value] = $propertyPath;
                    }
                    $data[$key] = $propertyPath;
                } elseif ($pos === 1 && $value[0] === '\\') {
                    $data[$key] = substr($value, 1);
                }
            }
        }

        return $data;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $value
     * @return PropertyPath
     * @throws \InvalidArgumentException
     */
    protected function parsePropertyPath($value)
    {
        $property = substr($value, 1);

        if (0 === strpos($property, '.')) {
            $property = substr($property, 1);
        } elseif ($this->prefix) {
            $property = $this->prefix . '.' .  $property;
        }

        return new PropertyPath($property);
    }
}
