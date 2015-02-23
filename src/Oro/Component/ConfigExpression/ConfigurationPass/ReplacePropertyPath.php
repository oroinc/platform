<?php

namespace Oro\Component\ConfigExpression\ConfigurationPass;

use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

/**
 * Passes through configuration array and replaces parameter strings ($parameter.name)
 * with appropriate PropertyPath objects.
 * To pass a string starts with $ just add \ before $, for example
 * \$parameter.name is converted to string "$parameter.name" rather than PropertyPath object.
 */
class ReplacePropertyPath implements ConfigurationPassInterface
{
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
                    $data[$key] = new PropertyPath(substr($value, 1));
                } elseif ($pos === 1 && $value[0] === '\\') {
                    $data[$key] = substr($value, 1);
                }
            }
        }

        return $data;
    }
}
