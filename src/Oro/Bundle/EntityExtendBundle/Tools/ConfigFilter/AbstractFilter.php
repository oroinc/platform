<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

abstract class AbstractFilter
{
    /**
     * Execute a filter in the same manner as calling a function
     *
     * @return bool
     */
    public function __invoke()
    {
        $args = func_get_args();
        if (count($args) !== 1) {
            throw new \InvalidArgumentException('Expected one argument.');
        }

        $config = $args[0];
        if (!$config instanceof ConfigInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument of type "%s", "%s" given.',
                    'Oro\Bundle\EntityConfigBundle\Config\ConfigInterface',
                    is_object($config) ? get_class($config) : gettype($config)
                )
            );
        }

        return $this->apply($config);
    }

    /**
     * Apply a filter
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    abstract protected function apply(ConfigInterface $config);
}
