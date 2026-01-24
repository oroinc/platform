<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

/**
 * Defines the contract for providing function definitions to the query designer.
 *
 * Implementations of this interface are responsible for retrieving function definitions
 * based on function name, group name, and group type. Functions are organized into groups
 * (e.g., aggregation functions, date functions) to support categorized function discovery
 * and validation. This interface enables extensibility by allowing custom function providers
 * to register additional functions beyond the built-in set.
 */
interface FunctionProviderInterface
{
    /**
     * Returns function definition
     *
     * @param string $name
     * @param string $groupName
     * @param string $groupType
     * @return array
     * @throws InvalidConfigurationException if the function was not found
     */
    public function getFunction($name, $groupName, $groupType);
}
