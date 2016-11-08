<?php

namespace Oro\Component\ConfigExpression\ConfigurationPass;

use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Passes through configuration array and replaces parameter strings ($parameter.name)
 * with appropriate PropertyPath objects.
 *
 * To pass a string starts with $ just add \ before $, for example
 * \$parameter.name is converted to string "$parameter.name" rather than PropertyPath object.
 */
class ReplacePropertyPath implements ConfigurationPassInterface
{
    /** @var string|null */
    protected $prefix;

    /** @var PropertyPath[] */
    private $cache = [];

    /**
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function passConfiguration(array $data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->passConfiguration($value);
            } elseif ($this->isStringPropertyPath($value)) {
                $value = $this->parsePropertyPath($value);
            }
        }

        return $data;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isStringPropertyPath($path)
    {
        return is_string($path) && preg_match('/^\\\?\$\.?[a-zA-Z_\x7f-\xff][\.a-zA-Z0-9_\x7f-\xff\[\]]*$/', $path);
    }

    /**
     * @param string $value
     * @return PropertyPath
     * @throws \InvalidArgumentException
     */
    protected function parsePropertyPath($value)
    {
        $pos = strpos($value, '$');
        if ($pos === 0) {
            $property = substr($value, 1);

            if (0 === strpos($property, '.')) {
                $property = substr($property, 1);
            } elseif ($this->prefix) {
                $property = $this->prefix . '.' .  $property;
            }

            if (!isset($this->cache[$property])) {
                $this->cache[$property] = new PropertyPath($property);
            }
            $value = $this->cache[$property];
        } elseif ($pos === 1 && $value[0] === '\\') {
            $value = substr($value, 1);
        }

        return $value;
    }
}
