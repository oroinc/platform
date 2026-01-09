<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Provides common functionality for entity configuration filters.
 *
 * This base class implements the callable interface (`__invoke`) to allow filters to be used as functions,
 * validating that they receive a single {@see ConfigInterface} argument. Subclasses should implement
 * the `filter` method to define specific filtering logic for entity configurations.
 */
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
